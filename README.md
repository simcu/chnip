## 生成中国区IP段

使用方法:

> php run.php

将会在目录下生成一个 chnip.ipset 的规则文件,
需要iptables配合ipset插件

> ipset create chnip hash:net
> ipset restore < /root/chnip.ipset

然后就可以在iptables使用规则测试了.

> -A PROXY_FILTER -m set --match-set chnip dst -j RETURN