#!/bin/bash

# EZCAST Commons 
# Copyright (C) 2014 Université libre de Bruxelles
#
# Written by Michel Jansens <mjansens@ulb.ac.be>
# 		    Arnaud Wijns <awijns@ulb.ac.be>
#                   Antoine Dewilde
#
# This software is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 3 of the License, or (at your option) any later version.
#
# This software is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

#NAME: 		install_templates.sh
#DESCRIPTION: 	Generate templates in all available languages
#AUTHOR:	Université libre de Bruxelles

G='\033[32m\033[1m'
R='\033[31m\033[1m'
N='\033[0m'

if [ "$#" -lt 3 ]; then
    echo -e "$0 :${R} Not enough args.${N}";
    echo -e "${G}usage:${N} use 1 or 0 to specify whether the component should be installed or not.";
    echo "       $0 <ezPlayer_value> <ezManager_value> <ezAdmin_value>";
    exit 0;
fi;

default_path="/usr/bin/php";
#php_path=$default_php_path;

clear;
echo "*******************************************************************";
echo "*                                                                 *";
echo "*                    T E M P L A T E S                            *";
echo "*                                                                 *";
echo "*******************************************************************";
echo " ";
echo -e "${R}/!\WARNING : This script will override all existing files with ${N}";
echo -e "${R}             with the same name inside specified folders. ${N}";
echo " ";
echo "PHP is required to continue, please enter the 'php' bin (with tailing 'php')";
read -p "[default:$default_path]:" php_path ;
if [ "$php_path" == "" ]; then
    php_path=$default_path;
fi;
value=$( $php_path -v);
# Verify that a version of PHP is installed
if [[ "$value" != PHP* ]]; then
    check=0;
    echo "PHP does not seem to be installed at $php_path" ;
fi;
# Retry as long as PHP has not been found
while [ "$check" ==  "0" ]; do
    echo "If PHP5 is installed, please enter its path now (with tailing 'php')";
    echo "otherwise, please enter 'exit' to quit this script and install PHP5";
    read php_path;
    if [ "$php_path" == "exit" ]; then exit; fi;
    if [ "$php_path" == "" ];
    then
        php_path=$default_path;
    fi;
    value=$( $php_path -v );
    if [[ "$value" == PHP* ]]; then
        check=1;
    fi;
    php_path=$default_path;
done;
echo -e "${G}PHP found !${N}";
echo " ";

if [ $1 == 1 ]; then
    echo -e "${G}--- Starting EZplayer template installation...${N}";
    #EZplayer template
    ../ezplayer/install.sh $php_path;
fi;
echo " ";

if [ $2 == 1 ]; then
echo -e "${G}--- Starting EZmanager template installation...${N}";
#EZmanager template
../ezmanager/install.sh $php_path;
fi;
echo " ";

if [ $3 == 1 ]; then
echo -e "${G}--- Starting EZadmin template installation...${N}";
#EZadmin template
../ezadmin/install.sh $php_path;
fi;
echo " ";

echo -e "${G}--- The end.${N}";