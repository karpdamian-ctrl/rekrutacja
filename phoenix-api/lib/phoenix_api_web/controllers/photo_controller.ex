defmodule PhoenixApiWeb.PhotoController do
  use PhoenixApiWeb, :controller

  alias PhoenixApi.Repo
  alias PhoenixApi.Media.Photo
  import Ecto.Query

  plug PhoenixApiWeb.Plugs.Authenticate

  def index(conn, _params) do
    current_user = conn.assigns.current_user

    photos =
      Photo
      |> where([p], p.user_id == ^current_user.id)
      |> select([p], %{id: p.id, photo_url: p.photo_url})
      |> Repo.all()

    json(conn, %{photos: photos})
  end
end
