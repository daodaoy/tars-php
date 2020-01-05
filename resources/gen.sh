#!/bin/bash

dir=$(dirname $0)

# tars-gen --client -f -n 'wenbinye\tars\stat' -s StatF=tars.tarsstat.StatObj             -t $dir/StatF.tars     -o $dir/../src/stat 
# tars-gen --client -f -n 'wenbinye\tars\stat' -s PropertyF=tars.tarsproperty.PropertyObj -t $dir/PropertyF.tars -o $dir/../src/stat
# tars-gen --client -f -n 'wenbinye\tars\stat' -s ServerF=tars.tarsnode.ServerObj         -t $dir/NodeF.tars     -o $dir/../src/stat
# tars-gen --client -f -n 'wenbinye\tars\log'  -s Log=tars.tarslog.LogObj                 -t $dir/LogF.tars      -o $dir/../src/log
tars-gen --client -f -n 'wenbinye\tars\registry' -s QueryF=tars.tarsregistry.QueryObj -t $dir/QueryF.tars     -o $dir/../src/registry
