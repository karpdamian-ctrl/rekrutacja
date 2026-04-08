defmodule PhoenixApi.Accounts.User do
  use Ecto.Schema
  import Ecto.Changeset

  schema "users" do
    field :api_token, :string

    has_many :photos, PhoenixApi.Media.Photo

    timestamps()
  end

  @doc false
  def changeset(user, attrs) do
    user
    |> cast(attrs, [:api_token])
    |> validate_required([:api_token])
    |> unique_constraint(:api_token)
  end
end
