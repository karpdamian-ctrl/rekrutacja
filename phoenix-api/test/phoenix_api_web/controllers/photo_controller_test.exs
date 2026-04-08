defmodule PhoenixApiWeb.PhotoControllerTest do
  use PhoenixApiWeb.ConnCase

  alias PhoenixApi.Repo
  alias PhoenixApi.Accounts.User
  alias PhoenixApi.Media.Photo

  setup do
    user =
      %User{}
      |> User.changeset(%{api_token: "valid_test_token_123"})
      |> Repo.insert!()

    other_user =
      %User{}
      |> User.changeset(%{api_token: "other_user_token_456"})
      |> Repo.insert!()

    photo1 =
      %Photo{}
      |> Photo.changeset(%{
        photo_url: "https://example.com/photo1.jpg",
        camera: "Canon EOS R5",
        lens: "RF 24-70mm f/2.8",
        settings: "Manual mode",
        description: "Beautiful sunset",
        location: "Beach",
        focal_length: "50mm",
        aperture: "f/2.8",
        shutter_speed: "1/200",
        iso: 100,
        user_id: user.id
      })
      |> Repo.insert!()

    photo2 =
      %Photo{}
      |> Photo.changeset(%{
        photo_url: "https://example.com/photo2.jpg",
        camera: "Sony A7III",
        lens: "FE 85mm f/1.8",
        description: "Portrait shot",
        location: "Studio",
        user_id: user.id
      })
      |> Repo.insert!()

    _other_photo =
      %Photo{}
      |> Photo.changeset(%{
        photo_url: "https://example.com/photo3.jpg",
        camera: "Nikon Z6",
        user_id: other_user.id
      })
      |> Repo.insert!()

    {:ok, user: user, other_user: other_user, photo1: photo1, photo2: photo2}
  end

  describe "GET /api/photos" do
    test "returns photos for authenticated user", %{conn: conn, photo1: photo1, photo2: photo2} do
      conn =
        conn
        |> put_req_header("access-token", "valid_test_token_123")
        |> get("/api/photos")

      assert json_response(conn, 200) == %{
               "photos" => [
                 %{"id" => photo1.id, "photo_url" => photo1.photo_url},
                 %{"id" => photo2.id, "photo_url" => photo2.photo_url}
               ]
             }
    end

    test "returns only id and photo_url fields", %{conn: conn, photo1: photo1} do
      conn =
        conn
        |> put_req_header("access-token", "valid_test_token_123")
        |> get("/api/photos")

      response = json_response(conn, 200)
      photos = response["photos"]

      assert length(photos) > 0

      first_photo = List.first(photos)
      assert Map.keys(first_photo) |> Enum.sort() == ["id", "photo_url"]
      assert first_photo["id"] == photo1.id
      assert first_photo["photo_url"] == photo1.photo_url
      refute Map.has_key?(first_photo, "camera")
      refute Map.has_key?(first_photo, "lens")
      refute Map.has_key?(first_photo, "description")
    end

    test "returns empty array when user has no photos", %{conn: conn} do
      new_user =
        %User{}
        |> User.changeset(%{api_token: "new_user_token"})
        |> Repo.insert!()

      conn =
        conn
        |> put_req_header("access-token", "new_user_token")
        |> get("/api/photos")

      assert json_response(conn, 200) == %{"photos" => []}
    end

    test "returns 401 when access-token header is missing", %{conn: conn} do
      conn = get(conn, "/api/photos")

      assert json_response(conn, 401) == %{
               "errors" => %{"detail" => "Unauthorized"}
             }
    end

    test "returns 401 when access-token is invalid", %{conn: conn} do
      conn =
        conn
        |> put_req_header("access-token", "invalid_token")
        |> get("/api/photos")

      assert json_response(conn, 401) == %{
               "errors" => %{"detail" => "Unauthorized"}
             }
    end

    test "different users see only their own photos", %{conn: conn} do
      conn =
        conn
        |> put_req_header("access-token", "other_user_token_456")
        |> get("/api/photos")

      response = json_response(conn, 200)
      assert length(response["photos"]) == 1
      assert Enum.at(response["photos"], 0)["photo_url"] == "https://example.com/photo3.jpg"
    end
  end
end
