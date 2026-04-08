defmodule PhoenixApi.Media.Photo do
  use Ecto.Schema
  import Ecto.Changeset

  schema "photos" do
    field :photo_url, :string
    field :camera, :string
    field :lens, :string
    field :settings, :string
    field :description, :string
    field :location, :string
    field :focal_length, :string
    field :aperture, :string
    field :shutter_speed, :string
    field :iso, :integer
    field :taken_at, :utc_datetime

    belongs_to :user, PhoenixApi.Accounts.User

    timestamps()
  end

  @doc false
  def changeset(photo, attrs) do
    photo
    |> cast(attrs, [
      :photo_url,
      :camera,
      :lens,
      :settings,
      :description,
      :location,
      :focal_length,
      :aperture,
      :shutter_speed,
      :iso,
      :taken_at,
      :user_id
    ])
    |> validate_required([:photo_url, :user_id])
    |> foreign_key_constraint(:user_id)
  end
end

