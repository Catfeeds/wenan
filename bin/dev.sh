#!/bin/sh

## 测试环境部署脚本

PRG="$0"

PRGDIR=`dirname "$PRG"`

PROJECTDIR=`cd "$PRGDIR/.." >/dev/null; pwd`

DEV_DIR="$PROJECTDIR"/bin/dev

## 替换文件

cp "$DEV_DIR"/.env "$PROJECTDIR"/.env

