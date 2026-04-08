defmodule PhoenixApi.Media.PhotoTest do
  use PhoenixApi.DataCase

  alias PhoenixApi.Accounts.User
  alias PhoenixApi.Media.Photo
  alias PhoenixApi.Repo

  describe "changeset/2" do
    test "is valid with required fields only" do
      user = insert_user("photo_required_fields_token")

      changeset =
        Photo.changeset(%Photo{}, %{
          photo_url: "https://example.com/photo.jpg",
          user_id: user.id
        })

      assert changeset.valid?
    end

    test "casts optional metadata fields" do
      user = insert_user("photo_optional_fields_token")
      taken_at = DateTime.from_naive!(~N[2026-04-09 11:30:00], "Etc/UTC")

      changeset =
        Photo.changeset(%Photo{}, %{
          photo_url: "https://example.com/optional-photo.jpg",
          camera: "Canon EOS R5",
          lens: "RF 24-70mm",
          settings: "Manual",
          description: "Sunset over the lake",
          location: "Mazury",
          focal_length: "50mm",
          aperture: "f/2.8",
          shutter_speed: "1/250",
          iso: 200,
          taken_at: taken_at,
          user_id: user.id
        })

      assert changeset.valid?
      assert get_change(changeset, :camera) == "Canon EOS R5"
      assert get_change(changeset, :taken_at) == taken_at
      assert get_change(changeset, :iso) == 200
    end

    test "requires photo_url and user_id" do
      changeset = Photo.changeset(%Photo{}, %{})

      refute changeset.valid?

      assert errors_on(changeset) == %{
               photo_url: ["can't be blank"],
               user_id: ["can't be blank"]
             }
    end

    test "enforces existing user_id" do
      changeset =
        %Photo{}
        |> Photo.changeset(%{
          photo_url: "https://example.com/invalid-user-photo.jpg",
          user_id: -1
        })

      assert {:error, changeset} = Repo.insert(changeset)
      assert errors_on(changeset) == %{user_id: ["does not exist"]}
    end
  end

  defp insert_user(token) do
    %User{}
    |> User.changeset(%{api_token: token})
    |> Repo.insert!()
  end
end
