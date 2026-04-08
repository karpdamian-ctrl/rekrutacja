defmodule PhoenixApi.Repo.Migrations.CreatePhotos do
  use Ecto.Migration

  def change do
    create table(:photos) do
      add :photo_url, :string, null: false
      add :camera, :string
      add :lens, :string
      add :settings, :text
      add :description, :text
      add :location, :string
      add :focal_length, :string
      add :aperture, :string
      add :shutter_speed, :string
      add :iso, :integer
      add :taken_at, :utc_datetime
      add :user_id, references(:users, on_delete: :delete_all), null: false

      timestamps()
    end

    create index(:photos, [:user_id])
  end
end
