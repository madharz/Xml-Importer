XML -> Redis -> Postgres (PHP + Docker)

Мінімальний пайплайн:
- index.php: парсить data/input.xml і кладе задачі у Redis (FIFO).
- worker.php: знімає задачі з Redis (BLPOP) і пише в Postgres (PDO).
- База ініціалізується скриптом docker/sql/init.sql.

Швидкий старт

docker compose up -d --build
docker compose exec php composer install
docker compose exec php php index.php  
docker compose exec php php worker.php 
