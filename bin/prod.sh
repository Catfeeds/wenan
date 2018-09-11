#!/bin/sh

## 生产环境部署脚本

PRG="$0"

PRGDIR=`dirname "$PRG"`

PROJECTDIR=`cd "$PRGDIR/.." >/dev/null; pwd`

PROD_DIR="$PROJECTDIR"/bin/prod

## 替换文件
cp "$PROD_DIR"/.env "$PROJECTDIR"/.env

