# Используем официальный образ Nginx
FROM nginx:1.22.1-alpine

# Копируем конфигурационные файлы
COPY nginx.conf /etc/nginx/nginx.conf
COPY sites-available/ps-1.docker.local.conf /etc/nginx/conf.d/default.conf
