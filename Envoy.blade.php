@servers(['web' => 'root@120.79.22.146'])

@task('chat', ['on' => 'web', 'confirm' => true])
    cd /data/wwwroot/default/message
    git clean -fd
    git fetch
    git reset --hard origin/master

    git submodule update

    /home/sh/chat_server.sh restart
@endtask
