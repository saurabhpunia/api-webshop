# api-webshop

# clone this git repository either by SSH or HTTP
git clone https://github.com/saurabhpunia/api-webshop.git
# or
git clone git@github.com:saurabhpunia/api-webshop.git

# create .env file using .env.example file
cp .env.example .env

# modify config in .env file as per requirement
composer install

# generate app key to make it secure
php artisan key:generate

# migrate the tables
php artisan migrate

# optimize the project
php artisan optimize

# serve the project
php artisan serve