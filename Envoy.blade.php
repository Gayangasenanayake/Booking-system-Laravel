@servers(['web' => 'deploy@54.254.46.159'])

@setup
// Sanity checks
if (empty($host)) {
exit('ERROR: $host var empty or not defined');
}
if (empty($user)) {
exit('ERROR: $user var empty or not defined');
}
if (empty($path)) {
exit('ERROR: $path var empty or not defined');
}
if (empty($build)) {
exit('ERROR: $build var empty or not defined');
}
if (empty($commit)) {
exit('ERROR: $commit var empty or not defined');
}

if (file_exists($path) || is_writable($path)) {
exit("ERROR: cannot access $path");
}

// Ensure given $path is a potential web directory (/home/* or /var/www/*)
if (!(preg_match("/(\/home\/|\/var\/www\/)/i", $path) === 1)) {
exit('ERROR: $path provided doesn\'t look like a web directory path?');
}

$date = ( new DateTime )->format('YmdHis');

$current_release_dir = $path . '/current';
$releases_dir = $path . '/releases';
$new_release_dir = $releases_dir . '/' . $date;

$remote = $user . '@' . $host . ':' . $new_release_dir;

// Command or path to invoke PHP
$php = empty($php) ? 'php' : $php;

@endsetup

@story('deploy')
create_folders
rsync
manifest_file
setup_symlinks
verify_install
activate_release
optimise
migrate
additional_tasks
cleanup
@endstory

@task('debug', ['on' => 'localhost'])
ls -la {{ $dir }}
@endtask

@task('create_folders', ['on' => 'web'])
echo "*Create storage folder if not exist*"
mkdir -p {{ $path }}/storage

echo "*Create releases folder if not exist*"
mkdir -p {{ $path }}/releases
@endtask

@task('rsync', ['on' => 'localhost'])
echo "* Deploying code from {{ $dir }} to {{ $remote }} *"
# https://explainshell.com/explain?cmd=rsync+-zrSlh+--exclude-from%3Ddeployment-exclude-list.txt+.%2F.+%7B%7B+%24remote+%7D%7D
rsync -zrSlh --stats --exclude-from=deployment-exclude-list.txt {{ $dir }}/ {{ $remote }}
@endtask

@task('manifest_file', ['on' => 'web'])
echo "* Writing deploy manifest file *"
echo -e "{\"build\":\""{{ $build }}"\", \"commit\":\""{{ $commit }}"\", \"branch\":\""{{ $branch }}"\"}" > {{ $new_release_dir }}/deploy-manifest.json
@endtask

@task('setup_symlinks', ['on' => 'web'])
echo "* Linking .env file to new release dir ({{ $path }}/.env -> {{ $new_release_dir }}/.env) *"
ln -nsf {{ $path }}/.env {{ $new_release_dir }}/.env

if [ -d {{ $new_release_dir }}/storage ]; then
echo "* Moving existing storage dir *"
mv {{ $new_release_dir }}/storage {{ $new_release_dir }}/storage.orig 2>/dev/null
echo "* Remove existing storage dir *"
rm -rf {{ $new_release_dir }}/storage
fi

echo "* Linking storage directory to new release dir ({{ $path }}/storage -> {{ $new_release_dir }}/storage) *"
ln -nsf {{ $path }}/storage {{ $new_release_dir }}/storage
ln -nsf {{ $path }}/storage/app/public {{ $new_release_dir }}/public/storage
@endtask

@task('verify_install', ['on' => 'web'])
echo "* Verifying install ({{ $new_release_dir }}) *"
cd {{ $new_release_dir }}
{{ $php }} artisan --version
@endtask

@task('activate_release', ['on' => 'web'])
echo "* Activating new release ({{ $new_release_dir }} -> {{ $current_release_dir }}) *"
ln -nsf {{ $new_release_dir }} {{ $current_release_dir }}

@endtask

@task('migrate', ['on' => 'web'])
echo '* Running migrations *'
cd {{ $new_release_dir }}
{{ $php }} artisan migrate --force
@endtask

@task('optimise', ['on' => 'web'])
echo '* Clearing cache and optimising *'
cd {{ $new_release_dir }}

{{ $php }} artisan cache:clear --quiet
{{ $php }} artisan config:clear --quiet
{{ $php }} artisan route:clear --quiet
{{ $php }} artisan view:clear --quiet

#or
#{{ $php }} artisan optimize:clear --quiet


echo "Queue restarted"
{{ $php }} artisan queue:restart --quiet

echo "Cache"
{{ $php }} artisan config:cache
{{ $php }} artisan view:cache

# Only use when no closure used in routes
{{ $php }} artisan optimize
{{ $php }} artisan route:cache

echo "Deployment ({{ $date }}) finished"
@endtask

@task('additional_tasks', ['on' => 'web'])
cd {{ $new_release_dir }}

echo "* Additional Tasks *"

@endtask

@task('cleanup', ['on' => 'web'])
echo "* Executing cleanup command in {{ $releases_dir }} *"

#find . -maxdepth 1 -name "20*" | sort | tail -n 2 | head -n1 | xargs rm -Rf
@endtask

@task('deployment_rollback')
#cd {{ $path }}
#ln -nsf {{ $path }}/$(find . -maxdepth 1 -name "20*" | sort | tail -n 2 | head -n1) {{ $path }}/current
echo "Rolled back to $(find . -maxdepth 1 -name "20*" | sort | tail -n 2 | head -n1)"
@endtask
