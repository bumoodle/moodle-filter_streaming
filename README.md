JWPlayer Filter for Moodle@BU
==============================

Allows streaming of FLV and MP4 files using JWPlayer, via Flash or HTML5. As a consequence, these videos should stream over most mobile devices, including smartphones and tablets.

Originally authored by Nicolas Connault.
Modified by Kyle Temkin, working for Binghamton University <http://www.binghamton.edu>

To install Moodle 2.1+ using git, execute the following commands in the root of your Moodle install:

    git clone git://github.com/ktemkin/moodle-filter_jwplayer.git filter/jwplayerfilter
    echo '/filter/jwplayerfilter' >> .git/info/exclude
    
Or, extract the following zip in your_moodle_root/filter/:

    https://github.com/ktemkin/moodle-filter_jwplayer/zipball/master
