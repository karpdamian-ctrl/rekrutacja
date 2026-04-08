defmodule PhoenixApi.Accounts.UserTest do
  use PhoenixApi.DataCase

  alias PhoenixApi.Accounts.User
  alias PhoenixApi.Repo

  describe "changeset/2" do
    test "is valid with api_token" do
      changeset = User.changeset(%User{}, %{api_token: "test_token_123"})

      assert changeset.valid?
    end

    test "requires api_token" do
      changeset = User.changeset(%User{}, %{})

      refute changeset.valid?
      assert errors_on(changeset) == %{api_token: ["can't be blank"]}
    end

    test "enforces unique api_token" do
      %User{}
      |> User.changeset(%{api_token: "duplicate_token"})
      |> Repo.insert!()

      changeset =
        %User{}
        |> User.changeset(%{api_token: "duplicate_token"})

      assert {:error, changeset} = Repo.insert(changeset)
      assert errors_on(changeset) == %{api_token: ["has already been taken"]}
    end
  end
end
