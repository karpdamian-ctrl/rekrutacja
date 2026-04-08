defmodule PhoenixApi.RateLimit.PhotoImportLimiterTest do
  use ExUnit.Case, async: false

  alias PhoenixApi.RateLimit.PhotoImportLimiter

  setup do
    :ok = PhotoImportLimiter.reset!()
  end

  test "allows imports below configured limits" do
    :ok = PhotoImportLimiter.reset!(user_limit: 2, global_limit: 3)

    assert :ok = PhotoImportLimiter.allow_import(1)
    assert :ok = PhotoImportLimiter.allow_import(1)
  end

  test "blocks imports when user limit is exceeded" do
    :ok = PhotoImportLimiter.reset!(user_limit: 1, global_limit: 10)

    assert :ok = PhotoImportLimiter.allow_import(1)
    assert {:error, :user_limit_exceeded} = PhotoImportLimiter.allow_import(1)
  end

  test "keeps per-user counters independent" do
    :ok = PhotoImportLimiter.reset!(user_limit: 1, global_limit: 10)

    assert :ok = PhotoImportLimiter.allow_import(1)
    assert :ok = PhotoImportLimiter.allow_import(2)
    assert {:error, :user_limit_exceeded} = PhotoImportLimiter.allow_import(1)
  end

  test "blocks imports when global limit is exceeded" do
    :ok = PhotoImportLimiter.reset!(user_limit: 10, global_limit: 2)

    assert :ok = PhotoImportLimiter.allow_import(1)
    assert :ok = PhotoImportLimiter.allow_import(2)
    assert {:error, :global_limit_exceeded} = PhotoImportLimiter.allow_import(3)
  end

  test "drops outdated requests after the configured window" do
    :ok = PhotoImportLimiter.reset!(user_limit: 1, user_window_ms: 5, global_limit: 10)

    assert :ok = PhotoImportLimiter.allow_import(1)
    assert {:error, :user_limit_exceeded} = PhotoImportLimiter.allow_import(1)

    Process.sleep(10)

    assert :ok = PhotoImportLimiter.allow_import(1)
  end
end
