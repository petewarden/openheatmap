#!/bin/bash -x

SSH_KEY=/Users/petewarden/.ssh/ohm
LOCAL_PATH=/Users/petewarden/projects/openheatmap/website/
STATIC_PATH=${LOCAL_PATH}../static/
SERVER_PATH=/localmnt/openheatmap.com/
S3_BUCKET=s3://static.openheatmap.com/

#SERVER_LIST=( ec2-204-236-225-17.compute-1.amazonaws.com ec2-75-101-175-72.compute-1.amazonaws.com ec2-184-72-137-100.compute-1.amazonaws.com)
SERVER_LIST=( openheatmap.com )
SERVER_COUNT=${#SERVER_LIST[@]}

for ((i=0;i<$SERVER_COUNT;i++)); do

  echo "Uploading to ${SERVER_LIST[${i}]}";

  SERVER_HOST=ubuntu@${SERVER_LIST[${i}]}
  SERVER_FULL=${SERVER_HOST}:${SERVER_PATH}

  rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}* ${SERVER_FULL}

#  rsync -e "ssh -i $SSH_KEY" -avz /mnt/geodict/* ${SERVER_HOST}:/localmnt/geodict/

done

s3cmd sync --config ~/.s3cfg_mailana --recursive --parallel --acl-public -p ${STATIC_PATH} s3://static.openheatmap.com/
