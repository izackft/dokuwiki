# instalar os pacotes abaixo

yum -y install openssl-devel
yum -y install apr apr-util
yum -y install apr-devel
yum -y install apr apr-util-devel

#LD_LIBRARY_PATH="$LD_LIBRARY_PATH:/usr/local/apr/lib:/usr/local/aprutil/bin:/usr/local/pcre/lib:/usr/local/openldap/lib:/usr/local/openssl/lib/usr/local/pcre/lib:/usr/local/apr/lib:/usr/local/aprutil/lib:/usr/lib64"
export LD_LIBRARY_PATH

export PATH=$PATH:/usr/local/apr/bin:/usr/local/aprutil/bin:/usr/local/pcre/bin

# ./configure --prefix=/usr/local/apache --enable-mods-shared=all --enable-proxy --enable-expires --enable-vhost-alias --with-ssl --enable-so --with-pcre=/usr/local/pcre --with-apr=/usr/local/apr --with-apr-util=/usr/local/aprutil

./configure --enable-so --prefix=/usr/local/apache2 --enable-mods-shared=all --enable-proxy --enable-expires --enable-vhost-alias --with-ssl --enable-so --with-pcre=/usr/local/pcre


ERRO=$?
#if [ $ERRO -gt 0 ] ; then echo -e "\nodou com erro\n" ; else echo -e "\nrodou ok\n" ; fi

#echo -e "\nTecle <RETURN> ..\c" ; read stop

make

#ERRO=$?
#if [ $ERRO -gt 0 ] ; then echo -e "\nrodou com erro\n" ; else echo -e "\nrodou ok\n" ; fi

#echo -e "\nTecle <RETURN> ..\c" ; read stop

make install

#ERRO=$?
#if [ $ERRO -gt 0 ] ; then echo -e "\nrodou com erro\n" ; else echo -e "\nrodou ok\n" ; fi

#echo -e "\nTecle <RETURN> ..\c" ; read stop

