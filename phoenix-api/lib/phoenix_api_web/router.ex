defmodule PhoenixApiWeb.Router do
  use PhoenixApiWeb, :router

  pipeline :api do
    plug :accepts, ["json"]
  end

  scope "/api", PhoenixApiWeb do
    pipe_through :api

    get "/photos", PhotoController, :index
  end
end
