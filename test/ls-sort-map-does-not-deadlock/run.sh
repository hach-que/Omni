#!/bin/omni

ls test:/ls-sort-map-does-not-deadlock | sort -f (() => ($_->testID)) | () => ($_->fileName + " " + $_->testID)

