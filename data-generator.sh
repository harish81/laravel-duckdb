#!/bin/bash

random_persons="Kailash Sequoia Sundar Devki John Lusineh Anil Alex Vipul Pooja"

hexdump -v -e '5/1 "%02x""\n"' /dev/urandom |
  awk -v OFS=',' -v random_persons="$random_persons" -v r_date="$(date "+%Y-%m-%d" -d "-$(( $RANDOM % 2000 + 1 )) days")" '
    NR == 1 { print "ID", "PERSON", "FOO_CODE", "VALUE", "DATE" }
    {
      $2=strftime("%Y%m%d",int(315532800000+rand()*(1681182619359-315532800000+1)));
      split(random_persons,rp," ");
      print substr($0, 1, 8), rp[substr($0, 1, 1)+1], substr($0, 9, 2), int(NR * 32768 * rand()), strftime("%Y-%m-%d",int(systime()-int(NR * 1186400 * rand())))
    }' |
  head -n "$1" > $2