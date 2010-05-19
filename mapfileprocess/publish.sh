#!/bin/sh

SSH_KEY=/Users/petewarden/.ec2/id_rsa-pstam-keypair
SERVER_HOST=root@openheatmap.com

LOCAL_PATH=/Users/petewarden/Projects/openheatmap/website/
STATIC_PATH=${LOCAL_PATH}../static/
SERVER_PATH=/vol/www/openheatmap/
SERVER_FULL=${SERVER_HOST}:${SERVER_PATH}
S3_BUCKET=s3://static.openheatmap.com/

echo "**Uploading files**"

rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}*.php ${SERVER_FULL}
rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}*.html ${SERVER_FULL}

s3cmd -P -f put maprender/bin-debug/maprender.swf ${S3_BUCKET}openheatmap.swf
s3cmd -P -f -r put ${STATIC_PATH}scripts ${S3_BUCKET}
s3cmd -P -f -r put ${STATIC_PATH}css ${S3_BUCKET}
s3cmd -P -f -r put ${STATIC_PATH}images ${S3_BUCKET}

