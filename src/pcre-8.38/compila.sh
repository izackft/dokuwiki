aclocal
automake

#./configure --prefix=/usr/local/pcre  --enable-utf --enable-unicode-properties --enable-jit --enable-pcre16 --enable-pcre32
#ERRO=$?
./configure --prefix=/usr/local/pcre --enable-UTF-8 --enable-unicode-properties --with-pcre=/usr/local/pcre/bin/pcre-config


make

make install

