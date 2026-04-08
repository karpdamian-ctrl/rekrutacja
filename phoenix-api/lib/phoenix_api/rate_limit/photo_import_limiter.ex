defmodule PhoenixApi.RateLimit.PhotoImportLimiter do
  @moduledoc false

  use GenServer

  @default_user_limit 5
  @default_user_window_ms 10 * 60 * 1_000
  @default_global_limit 1_000
  @default_global_window_ms 60 * 60 * 1_000

  def start_link(opts \\ []) do
    GenServer.start_link(__MODULE__, opts, name: __MODULE__)
  end

  def allow_import(user_id) when is_integer(user_id) do
    GenServer.call(__MODULE__, {:allow_import, user_id})
  end

  def reset!(opts \\ []) do
    GenServer.call(__MODULE__, {:reset, opts})
  end

  @impl true
  def init(opts) do
    {:ok, build_state(opts)}
  end

  @impl true
  def handle_call({:allow_import, user_id}, _from, state) do
    now = System.monotonic_time(:millisecond)
    global_requests = prune_requests(state.global_requests, now, state.global_window_ms)

    user_requests =
      prune_requests(Map.get(state.user_requests, user_id, []), now, state.user_window_ms)

    cond do
      length(global_requests) >= state.global_limit ->
        {:reply, {:error, :global_limit_exceeded}, %{state | global_requests: global_requests}}

      length(user_requests) >= state.user_limit ->
        updated_users =
          if user_requests == [] do
            Map.delete(state.user_requests, user_id)
          else
            Map.put(state.user_requests, user_id, user_requests)
          end

        {:reply, {:error, :user_limit_exceeded},
         %{state | global_requests: global_requests, user_requests: updated_users}}

      true ->
        updated_user_requests = [now | user_requests]

        {:reply, :ok,
         %{
           state
           | global_requests: [now | global_requests],
             user_requests: Map.put(state.user_requests, user_id, updated_user_requests)
         }}
    end
  end

  def handle_call({:reset, opts}, _from, state) do
    {:reply, :ok, build_state(opts, state)}
  end

  defp build_state(opts, state \\ nil) do
    runtime_config = Application.get_env(:phoenix_api, __MODULE__, [])

    %{
      user_limit:
        Keyword.get(
          opts,
          :user_limit,
          state_value(state, :user_limit, runtime_config, @default_user_limit)
        ),
      user_window_ms:
        Keyword.get(
          opts,
          :user_window_ms,
          state_value(state, :user_window_ms, runtime_config, @default_user_window_ms)
        ),
      global_limit:
        Keyword.get(
          opts,
          :global_limit,
          state_value(state, :global_limit, runtime_config, @default_global_limit)
        ),
      global_window_ms:
        Keyword.get(
          opts,
          :global_window_ms,
          state_value(state, :global_window_ms, runtime_config, @default_global_window_ms)
        ),
      user_requests: %{},
      global_requests: []
    }
  end

  defp state_value(nil, key, runtime_config, default),
    do: Keyword.get(runtime_config, key, default)

  defp state_value(state, key, _runtime_config, _default),
    do: Map.fetch!(state, key)

  defp prune_requests(requests, now, window_ms) do
    threshold = now - window_ms
    Enum.filter(requests, &(&1 > threshold))
  end
end
