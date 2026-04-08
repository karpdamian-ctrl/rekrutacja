defmodule PhoenixApiWeb.Endpoint do
  use Phoenix.Endpoint, otp_app: :phoenix_api

  socket "/socket", PhoenixApiWeb.UserSocket,
    websocket: true,
    longpoll: false

  plug Plug.RequestId
  plug Plug.Telemetry, event_prefix: [:phoenix, :endpoint]

  plug Plug.Parsers,
    parsers: [:urlencoded, :multipart, :json],
    pass: ["*/*"],
    json_decoder: Phoenix.json_library()

  plug Plug.MethodOverride
  plug Plug.Head
  plug PhoenixApiWeb.Router
end
