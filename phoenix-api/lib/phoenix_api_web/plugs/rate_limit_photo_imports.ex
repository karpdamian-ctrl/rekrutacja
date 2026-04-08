defmodule PhoenixApiWeb.Plugs.RateLimitPhotoImports do
  @moduledoc false

  import Plug.Conn
  import Phoenix.Controller

  alias PhoenixApi.RateLimit.PhotoImportLimiter

  def init(opts), do: opts

  def call(%Plug.Conn{assigns: %{current_user: current_user}} = conn, _opts) do
    case PhotoImportLimiter.allow_import(current_user.id) do
      :ok ->
        conn

      {:error, _reason} ->
        conn
        |> put_status(:too_many_requests)
        |> json(%{errors: %{detail: "Photo import rate limit exceeded"}})
        |> halt()
    end
  end

  def call(conn, _opts), do: conn
end
