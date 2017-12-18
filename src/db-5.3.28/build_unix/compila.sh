CPPFLAGS="-I/usr/local/openssl/include"
export CPPFLAGS
LDFLAGS="-L/usr/local/openssl"
export LDFLAGS
LD_LIBRARY_PATH="/usr/local/opensssl/lib"
export LD_LIBRARY_PATH

../dist/configure --prefix=/usr/local/db5.3
make
make install
