# ter instalado essa lib
# yum -y install libxml2-devel

CPPFLAGS="-I/usr/local/db5.3/include -L/usr/local/openldap/include -L/usr/local/openssl/include -L/usr/local/pcre/include -fPIC"
export CPPFLAGS

LDFLAGS="-L/usr/local/db5.3 -L/usr/local/openldap -L/usr/local/openssl -L/usr/local/pcre"
export LDFLAGS

LD_LIBRARY_PATH="$LD_LIBRARY_PATH:/usr/local/db5.3/lib:/usr/local/openldap/lib:/usr/local/openssl/lib/usr/local/pcre/lib"
export LD_LIBRARY_PATH

export PATH=$PATH:/usr/local/apr/bin:/usr/local/aprutil/bin:/usr/local/pcre/bin

# ./configure --prefix=/usr/local/php --with-apxs2=/usr/local/apache/bin/apxs --with-pcre-regex=/usr/local/pcre  --enable-calendar --enable-dba=shared --with-db4=/usr/local/db5.3 --enable-ftp  --enable-soap  --with-mysql-sock=/usr/local/mysql/var/mysqld.sock --enable-mbstring --with-curl --with-pdo-mysql 

# ./configure --prefix=/usr/local/php --with-apxs2=/usr/local/apache/bin/apxs  --with-pcre-regex=/usr/local/pcre  --enable-calendar --enable-dba=shared --with-db4=/usr/local/db5.3 --enable-ftp  --enable-soap  --with-mysql-sock=/usr/local/mysql/var/mysqld.sock --enable-mbstring --with-curl --with-pdo-mysql --disable-ipv6 --with-openssl-dir=/usr/local/openssl --with-openssl=/usr/local/openssl

# Sem MySQL

# ./configure --prefix=/usr/local/php --with-apxs2=/usr/local/apache/bin/apxs  --with-pcre-regex=/usr/local/pcre  --enable-calendar --with-db4=/usr/local/db5.3 --enable-ftp  --enable-soap  --enable-mbstring --with-curl --disable-ipv6 --with-openssl-dir=/usr/local/openssl --with-openssl=/usr/local/openssl

#./configure \
#            --prefix=/usr/local/php \
#            --with-pcre-regex=/usr/local/pcre \
            # --with-apxs2=/usr/local/apache/bin/apxs 
            # --with-openssl-dir=/usr/include/openssl \
            # --with-libdir=lib64
./configure --prefix=/usr/local/php --disable-ipv6 --with-zlib --enable-calendar --with-curl --enable-dba --enable-ftp --enable-sockets --enable-zip --with-libdir=lib64 --with-freetype-dir --with-apxs2=/usr/local/apache2/bin/apxs --with-openssl --enable-mbstring --enable-zip --with-pcre-dir --with-ldap

ERRO=$?
if [ $ERRO -gt 0 ] ; then echo -e "\nrodou com erro\n" ; exit ; else echo -e "\nrodou ok\n" ; fi

make

ERRO=$?
if [ $ERRO -gt 0 ] ; then echo -e "\nrodou com erro\n" ; exit ; else echo -e "\nrodou ok\n" ; fi

make install

ERRO=$?
if [ $ERRO -gt 0 ] ; then echo -e "\nrodou com erro\n" ; exit ; else echo -e "\nrodou ok\n" ; fi

