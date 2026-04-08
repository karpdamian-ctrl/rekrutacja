defmodule PhoenixApiWeb.UserSocket do
  use Phoenix.Socket

  # Define your channels here
  # channel "room:*", PhoenixApiWeb.RoomChannel

  @impl true
  def connect(_params, socket, _connect_info), do: {:ok, socket}

  @impl true
  def id(_socket), do: nil
end
