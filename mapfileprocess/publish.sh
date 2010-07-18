#!/bin/sh

SSH_KEY=/Users/petewarden/.ec2/id_rsa-pstam-keypair
LOCAL_PATH=/Users/petewarden/Projects/openheatmap/website/
STATIC_PATH=${LOCAL_PATH}../static/
SERVER_PATH=/mnt/openheatmap.com/
S3_BUCKET=s3://static.openheatmap.com/

SERVER_LIST=( ec2-204-236-225-17.compute-1.amazonaws.com ec2-75-101-175-72.compute-1.amazonaws.com)
SERVER_COUNT=${#SERVER_LIST[@]}

for ((i=0;i<$SERVER_COUNT;i++)); do

  echo "Uploading to ${SERVER_LIST[${i}]}";

  SERVER_HOST=root@${SERVER_LIST[${i}]}
  SERVER_FULL=${SERVER_HOST}:${SERVER_PATH}

  rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}*.xml ${SERVER_FULL}
  rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}*.php ${SERVER_FULL}
  rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}*.html ${SERVER_FULL}

  rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}examples/* ${SERVER_FULL}/examples/

done

s3cmd -P -f put ${LOCAL_PATH}../maprender/bin-debug/maprender.swf ${S3_BUCKET}openheatmap.swf
s3cmd -P -f -r put ${STATIC_PATH}scripts ${S3_BUCKET}
s3cmd -P -f -r put ${STATIC_PATH}css ${S3_BUCKET}
s3cmd -P -f -r put ${STATIC_PATH}images ${S3_BUCKET}

