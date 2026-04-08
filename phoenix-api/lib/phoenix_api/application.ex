defmodule PhoenixApi.Application do
  @moduledoc false
  use Application

  def start(_type, _args) do
    children = [
      PhoenixApi.Repo,
      {Phoenix.PubSub, name: PhoenixApi.PubSub},
      PhoenixApiWeb.Endpoint,
      {Finch, name: PhoenixApiFinch}
    ]

    opts = [strategy: :one_for_one, name: PhoenixApi.Supervisor]
    Supervisor.start_link(children, opts)
  end

  def config_change(changed, _new, removed) do
    PhoenixApiWeb.Endpoint.config_change(changed, removed)
    :ok
  end
end
