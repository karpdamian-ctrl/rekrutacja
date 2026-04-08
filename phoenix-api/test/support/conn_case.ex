defmodule PhoenixApiWeb.ConnCase do
  @moduledoc """
  Test case for connection tests.
  """
  use ExUnit.CaseTemplate

  using do
    quote do
      use Phoenix.ConnTest
      import PhoenixApiWeb.Router.Helpers

      alias PhoenixApiWeb.Router.Helpers, as: Routes

      @endpoint PhoenixApiWeb.Endpoint
    end
  end

  setup tags do
    pid = Ecto.Adapters.SQL.Sandbox.start_owner!(PhoenixApi.Repo, shared: not tags[:async])
    on_exit(fn -> Ecto.Adapters.SQL.Sandbox.stop_owner(pid) end)
    {:ok, conn: Phoenix.ConnTest.build_conn()}
  end
end
