#!/bin/omni

: $logs = ($(ls systemd:/service/nginx)->loadLogs())
: $messages = $(iter $logs | () => ($_->MESSAGE))

