#!/usr/bin/env bash

# Usage:
#	sudo ./install-solr-tomcat.sh
#	sudo ./install-solr-tomcat.sh english german french

clear

TOMCAT_VERSION=8.0.30
SOLR_VERSION=4.10.4
EXT_SOLR_VERSION=3.2
SOLR_TYPO3_PLUGIN_VERSION=1.3.0
JAVA_VERSION=7
if [ -z "$SOLR_INSTALL_DIR" ]; then
	SOLR_INSTALL_DIR="/opt/solr-tomcat";
fi

GITBRANCH_PATH="release-$EXT_SOLR_VERSION.x"

APACHE_MIRROR="http://mirror.dkd.de/apache/"
APACHE_ARCHIVE="http://archive.apache.org/dist/"

# Set default language for cores to download to english, if no commandline parameters are given
if [ $# -eq 0 ]
then
	LANGUAGES=english
else
	LANGUAGES=$@
fi

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

progressfilt ()
{
	local flag=false c count cr=$'\r' nl=$'\n'
	while IFS='' read -d '' -rn 1 c
	do
		if $flag
		then
			printf '%c' "$c"
		else
			if [[ $c != $cr && $c != $nl ]]
			then
				count=0
			else
				((count++))
				if ((count > 1))
				then
					flag=true
				fi
			fi
		fi
	done
}

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

# wgetresource
# usage: wgetresource relative/filepath/inside/resourcesdir [justcheck]
# second parameter is optional, if set, do not download, only check if resource exists
wgetresource ()
{
	local wget_result

	if [ $BRANCH_TEST_RETURN -eq "0" ]
	then
		RESOURCE="https://raw.githubusercontent.com/TYPO3-Solr/ext-solr/$GITBRANCH_PATH/Resources/"$1
	else
		RESOURCE="https://raw.githubusercontent.com/TYPO3-Solr/ext-solr/master/Resources/"$1
	fi

	if [ "$2" ]
	then
		# If second parameter is set, just check if resource exists, no output
		wget -q --spider --no-check-certificate $RESOURCE
	else
		echo "wget $RESOURCE"
		wget --progress=bar:force --no-check-certificate $RESOURCE 2>&1 | progressfilt
	fi

	# return wget error code
	return $?
}

# check whether a given resource is available on a mirror
# if the resource is found it will download from the mirror
# it the resource is not found it will download from Apache archive
apachedownload ()
{
	# test mirror
	wget -q --spider "$APACHE_MIRROR$1"

	if [ $? -eq "0" ]
	then
		# download from mirror
		wget --progress=bar:force "$APACHE_MIRROR$1" 2>&1 | progressfilt
	else
		# download from archive
		wget --progress=bar:force "$APACHE_ARCHIVE$1" 2>&1 | progressfilt
	fi
}

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

# color echo http://www.faqs.org/docs/abs/HTML/colorizing.html

black="\033[30m"
red="\033[31m"
green="\033[32m"
yellow="\033[33m"
blue="\033[34m"
magenta="\033[35m"
cyan="\033[36m"
white="\033[37m"


# Color-echo, Argument $1 = message, Argument $2 = color
cecho ()
{
	local default_msg="No message passed."

	# Defaults to default message.
	message=${1:-$default_msg}

	# Defaults to black, if not specified.
	color=${2:-$black}

	echo -e "$color$message"

	# Reset text attributes to normal + without clearing screen.
	tput sgr0

	return
}

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Checking requirements." $green

PASSALLCHECKS=1

wget --version > /dev/null 2>&1
CHECK=$?
if [ $CHECK -ne "0" ]
then
	cecho "ERROR couldn't find wget." $red
	PASSALLCHECKS=0
fi

# test if release branch exists, if so we'll download from there
wget --no-check-certificate -q --spider https://raw.githubusercontent.com/TYPO3-Solr/ext-solr/$GITBRANCH_PATH/Resources/Solr/solr.xml
BRANCH_TEST_RETURN=$?

java -version > /dev/null 2>&1
CHECK=$?
if [ $CHECK -ne "0" ]
then
	cecho "ERROR couldn't find Java (Oracle Java is recommended)." $red
	PASSALLCHECKS=0
fi

JAVA_VERSION_INSTALLED=$(java -version 2>&1 | grep -Eom1 "[._0-9]{5,}")
# extract the main Java version from 1.7.0_11 => 7
JAVA_VERSION_INSTALLED=${JAVA_VERSION_INSTALLED:2:1}
# check if java version is 7 or newer
if [ $JAVA_VERSION_INSTALLED -lt $JAVA_VERSION ]
then
  cecho "You have installed Java version $JAVA_VERSION_INSTALLED. Please install Java $JAVA_VERSION or newer." $red
  PASSALLCHECKS=0
fi

ping -c 1 mirror.dkd.de > /dev/null 2>&1
CHECK=$?
if [ $CHECK -ne "0" ]
then
	cecho "ERROR couldn't ping Apache download mirror, try again using wget" $yellow
	wget -q --spider http://mirror.dkd.de/apache/
	if [ $? -ne "0" ]
	then
		cecho "ERROR Also couldn't reach the Apache download mirror using wget. Please check your internet connection." $red
		PASSALLCHECKS=0
	fi
fi

unzip -v > /dev/null 2>&1
CHECK=$?
if [ $CHECK -ne "0" ]
then
	cecho "ERROR: couldn't find unzip." $red
	PASSALLCHECKS=0
fi

# Check if solr scheme files etc. for specified languages are available
for LANGUAGE in ${LANGUAGES[*]}
do
	echo -n "Checking availability of language \"$LANGUAGE\": "
	wgetresource Solr/typo3cores/conf/"$LANGUAGE"/schema.xml justcheck
	if [ $? -ne 0 ]
	then
		cecho "ERROR: Could not find Solr configuration files for language \"$LANGUAGE\"" $red
		exit 1
	else cecho "passed" $green
	fi
done

if [ $PASSALLCHECKS -eq "0" ]
then
	cecho "Please install all missing requirements or fix any other errors listed above and try again." $red
	exit 1
else
	cecho "All requirements met, starting to install Solr." $green
fi

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

mkdir -p ${SOLR_INSTALL_DIR}
cd ${SOLR_INSTALL_DIR}

cecho "Downloading Apache Tomcat $TOMCAT_VERSION" $green
TOMCAT_MAINVERSION=`echo "$TOMCAT_VERSION" | cut -d'.' -f1`
apachedownload tomcat/tomcat-$TOMCAT_MAINVERSION/v$TOMCAT_VERSION/bin/apache-tomcat-$TOMCAT_VERSION.zip

cecho "Downloading Apache Solr $SOLR_VERSION" $green
apachedownload lucene/solr/$SOLR_VERSION/solr-$SOLR_VERSION.zip

cecho "Unpacking Apache Tomcat." $green
unzip -q apache-tomcat-$TOMCAT_VERSION.zip

cecho "Unpacking Apache Solr." $green
unzip -q solr-$SOLR_VERSION.zip

mv apache-tomcat-$TOMCAT_VERSION tomcat

cp solr-$SOLR_VERSION/dist/solr-$SOLR_VERSION.war tomcat/webapps/solr.war
cp solr-$SOLR_VERSION/example/lib/ext/*.jar tomcat/lib
cp solr-$SOLR_VERSION/example/resources/log4j.properties tomcat/lib/log4j.properties
cp -r solr-$SOLR_VERSION/example/solr .


# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Downloading TYPO3 Solr configuration files." $green
cd solr
SOLRDIR=`pwd`

for LANGUAGE in ${LANGUAGES[*]}
do
	cecho "Downloading configuration for language: $LANGUAGE" $green

	cd $SOLRDIR
	# create / download $LANGUAGE core configuration
	mkdir -p typo3cores/conf/$LANGUAGE
	cd typo3cores/conf/$LANGUAGE

	wgetresource Solr/typo3cores/conf/$LANGUAGE/protwords.txt
	wgetresource Solr/typo3cores/conf/$LANGUAGE/schema.xml
	wgetresource Solr/typo3cores/conf/$LANGUAGE/synonyms.txt

	if [ $LANGUAGE = "german" ]
	then
		wgetresource Solr/typo3cores/conf/$LANGUAGE/german-common-nouns.txt
	fi

	cd $SOLRDIR/typo3cores/conf
	wgetresource Solr/typo3cores/conf/$LANGUAGE/_schema_analysis_stopwords_$LANGUAGE.json
done

# download general configuration in ${SOLR_INSTALL_DIR}/solr/typo3cores/conf/
cecho "Downloading general configruation" $green
cd $SOLRDIR/typo3cores/conf
wgetresource Solr/typo3cores/conf/currency.xml
wgetresource Solr/typo3cores/conf/elevate.xml
wgetresource Solr/typo3cores/conf/general_schema_fields.xml
wgetresource Solr/typo3cores/conf/general_schema_types.xml
wgetresource Solr/typo3cores/conf/solrconfig.xml

# download core configuration file solr.xml in ${SOLR_INSTALL_DIR}/solr/
cd ../..
rm solr.xml
wgetresource Solr/solr.xml

# clean up
rm -rf collection1
rm -rf bin
rm -rf conf
rm -rf data
rm README.txt

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Configuring Apache Tomcat." $green
cd ${SOLR_INSTALL_DIR}/tomcat/conf

rm server.xml

wgetresource Tomcat/server.xml

cd ${SOLR_INSTALL_DIR}
mkdir -p tomcat/conf/Catalina/localhost
cd tomcat/conf/Catalina/localhost

# install context descriptor for the solr context/webapp, sets the solr.home property
wgetresource Tomcat/solr.xml
DEFAULT_INSTALL_PATH=/opt/solr-tomcat
# todo fix in template file
sed -i 's|'$DEFAULT_INSTALL_PATH'|'$SOLR_INSTALL_DIR'|g' Tomcat/solr.xml
cat Tomcat/solr.xml

# copy libs
cd ${SOLR_INSTALL_DIR}
cp -r solr-$SOLR_VERSION/dist solr/
cp -r solr-$SOLR_VERSION/contrib solr/

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Downloading the Solr TYPO3 plugin for access control. Version: $SOLR_TYPO3_PLUGIN_VERSION" $green
mkdir solr/typo3lib
cd solr/typo3lib
wget --progress=bar:force https://github.com/TYPO3-Solr/solr-typo3-plugin/releases/download/release-${SOLR_TYPO3_PLUGIN_VERSION//\./_}/solr-typo3-plugin-$SOLR_TYPO3_PLUGIN_VERSION.jar 2>&1 | progressfilt

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Setting permissions." $green
cd ${SOLR_INSTALL_DIR}
chmod a+x tomcat/bin/*

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Cleaning up." $green
rm -rf solr-$SOLR_VERSION.zip
rm -rf solr-$SOLR_VERSION
rm -rf apache-tomcat-$TOMCAT_VERSION.zip

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Starting Tomcat." $green
./tomcat/bin/startup.sh

cecho "Done." $green
cecho "Now browse to http://localhost:8080/solr/" $green
