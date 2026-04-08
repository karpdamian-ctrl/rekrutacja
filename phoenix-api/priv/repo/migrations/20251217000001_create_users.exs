defmodule PhoenixApi.Repo.Migrations.CreateUsers do
  use Ecto.Migration

  def change do
    create table(:users) do
      add :api_token, :string, null: false

      timestamps()
    end

    create unique_index(:users, [:api_token])
  end
end
