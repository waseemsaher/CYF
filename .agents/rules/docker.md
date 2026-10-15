# Docker Environment

The user runs everything via Docker Desktop.
Always provide commands using `docker compose exec backend php artisan ...` (or `docker exec -it cyf-backend php artisan ...`) whenever suggesting backend / artisan commands.
Never suggest running `php artisan ...` directly on the host machine without Docker.
