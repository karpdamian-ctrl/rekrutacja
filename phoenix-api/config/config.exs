import Config

config :phoenix_api,
  ecto_repos: [PhoenixApi.Repo]

config :phoenix_api, PhoenixApiWeb.Endpoint,
  url: [host: "localhost"],
  render_errors: [
    formats: [json: PhoenixApiWeb.ErrorJSON],
    layout: false
  ],
  pubsub_server: PhoenixApi.PubSub,
  live_view: [signing_salt: "secret"]

config :phoenix_api, PhoenixApiWeb.Gettext, default_locale: "en", locales: ~w(en pl)

config :logger, :console,
  format: "$time $metadata[$level] $message\n",
  metadata: [:request_id]

config :phoenix, :json_library, Jason

import_config "#{config_env()}.exs"
