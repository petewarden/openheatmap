#!/bin/sh

SSH_KEY=/Users/petewarden/.ec2/id_rsa-pstam-keypair
SERVER_HOST=root@openheatmap.com

LOCAL_PATH=/Users/petewarden/Projects/openheatmap/website/
SERVER_PATH=/vol/www/openheatmap/
SERVER_FULL=${SERVER_HOST}:${SERVER_PATH}

echo "**Uploading files**"

rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}*.php ${SERVER_FULL}
rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}*.html ${SERVER_FULL}
rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}scripts/*.js ${SERVER_FULL}scripts/
rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}css/*.css ${SERVER_FULL}css/
rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}images/* ${SERVER_FULL}images/
rsync -e "ssh -i $SSH_KEY" -avz ${LOCAL_PATH}images/colorpicker/* ${SERVER_FULL}images/colorpicker/

s3cmd -P -f put maprender/bin-debug/maprender.swf s3://static.openheatmap.com/openheatmap.swf
