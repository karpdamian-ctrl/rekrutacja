defmodule PhoenixApi.DataCase do
  @moduledoc """
  Helper for database tests with sandbox.
  """
  use ExUnit.CaseTemplate

  using do
    quote do
      alias PhoenixApi.Repo

      import Ecto
      import Ecto.Changeset
      import Ecto.Query
      import PhoenixApi.DataCase
    end
  end

  setup tags do
    pid = Ecto.Adapters.SQL.Sandbox.start_owner!(PhoenixApi.Repo, shared: not tags[:async])
    on_exit(fn -> Ecto.Adapters.SQL.Sandbox.stop_owner(pid) end)
    :ok
  end

  @doc """
  Helper to transform changeset errors into map of messages.
  """
  def errors_on(changeset) do
    Ecto.Changeset.traverse_errors(changeset, fn {message, opts} ->
      Enum.reduce(opts, message, fn {key, value}, acc ->
        String.replace(acc, "%{#{key}}", to_string(value))
      end)
    end)
  end
end
