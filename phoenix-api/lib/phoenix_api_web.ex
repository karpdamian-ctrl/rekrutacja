defmodule PhoenixApiWeb do
  def controller do
    quote do
      use Phoenix.Controller,
        namespace: PhoenixApiWeb,
        formats: [:json],
        layouts: []
      import Plug.Conn
      alias PhoenixApiWeb.Router.Helpers, as: Routes
    end
  end

  def router do
    quote do
      use Phoenix.Router

      import Plug.Conn
      import Phoenix.Controller
    end
  end

  def channel do
    quote do
      use Phoenix.Channel
      import PhoenixApiWeb.Gettext
    end
  end

  defmacro __using__(which) when is_atom(which), do: apply(__MODULE__, which, [])
end
