#!/bin/bash
post="010000000246ff7dab3076cc3b2a713355984a04a7142525fc5633514dbb4d40d56c881b5a010000006a47304402201c5e0794a4f831be7c9befb1bc208ef1a60bb7100f53d5eb21db679c7d01facd02202617e003889b0e4f138977d9eac267711a885af23a359ffce6b5e8d53267e988012103528ab4c649dd0d14a430f3dab591ee35baf0c5873b3340806673f13051c06d47ffffffff22341a028ee979d4b66d16155148691fdec238fd01347f764668cae6bd765612020000006b483045022100f342638c80f660ddd7b4ab6b3b2c7c8675c54fa3743d6a52b55099acc8bb5d9f02203c802e0d81f556bc75c583daa1961c1e30ce0de254eafa586a394538cee740a5012103528ab4c649dd0d14a430f3dab591ee35baf0c5873b3340806673f13051c06d47ffffffff03e0930400000000001976a914c48e8a082b363ba9a23d74a6a7ab35fe4f1174db88ac809fd500000000001976a9147379e3d07f807bcac7909568e9818faa7ebe0f2688aca0740400000000001976a9143ff6e3ced0533f182e5c006156d12cbbe3bb046988ac00000000";
# -H 'Content-Type: application/json;charset=utf-8'
curl --trace-time -v --data-binary 'rawtx=01}' -H 'Content-Type: application/x-www-form-urlencoded' https://kmdexplorer.ru/insight-api-komodo/tx/send
