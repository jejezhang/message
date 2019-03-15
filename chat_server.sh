#!/bin/sh
# description: start and reload swoole server

DIR=/data/wwwroot/default/message
PORT=8888

case "$1" in
    status)
        if [ `lsof -t -i:$PORT` ]; then
            echo "swoole server is running"
            exit 1
        fi

        echo "swoole is not running"
        ;;
    start)
        if [ `lsof -t -i:$PORT` ]; then
            echo "swoole server has already starting"
            exit 1
        fi

		nohup php $DIR/server.php > $DIR/log.log 2>&1 &
        echo "swoole server starting success"
        ;;
    stop)
        kill `lsof -t -i:$PORT`
        echo "swoole server has stop"
        ;;
    restart)
        $0 stop
        usleep 100000
        $0 start
        ;;
    *)
        echo "Usage: $0 {start|restart|stop|status}" >&2
        exit 1
esac

