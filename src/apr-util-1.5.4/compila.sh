#./configure --prefix=/usr/local/aprutil --enable-threads --enable-other-child --with-apr=/usr/local/apr
./configure --prefix=/usr/local/apr --with-ldap-include=/usr/local/openldap/include --with-ldap-lib=/usr/local/openldap/lib --with-apr=/usr/local/apr --with-ldap

./configure --prefix=/usr/local/apr --with-apr=/usr/local/apr


make

make install

