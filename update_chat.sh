#! /bin/bash
CODE_DIR=/data/wwwroot/default/message
BRANCH=$1
BRANCH=${BRANCH:=master}

# 更新代码
cd $CODE_DIR

git clean -fd
git fetch
git reset --hard origin/$BRANCH

git submodule update

./chat_server.sh
