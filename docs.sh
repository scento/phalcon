#!/bin/bash
DATA_DIR=`pwd`/Phalcon

DOCS_DIR=`pwd`/docs
DOCS_DIR_PHPUML=`pwd`/docs/phpuml
DOCS_DIR_PHPDOC=`pwd`/docs/phpdoc

LOG_PHPDOC=`pwd`/docs/phpdoc/log.txt

PATH_PHPDOC=$(type -P "phpdoc")
PATH_PHPUML=$(type -P "phpuml")

USE_PHPDOC=1
USE_PHPUML=1

#Checking for installed applications
echo "Checking for installed applications..."

if [[ -z "$PATH_PHPDOC" ]]; then
	USE_PHPDOC=0
fi
if [[ -z "$PATH_PHPUML" ]]; then
	USE_PHPUML=0
fi

#Generating directory structure
echo "Generating directory structure... "

if [[ -d "$DOCS_DIR" ]]; then
	rm -rf $DOCS_DIR
fi
mkdir $DOCS_DIR

if [ "$USE_PHPUML" == "1" ]; then
	if [[ -d "$DOCS_DIR_PHPUML" ]]; then
		rm -rf $DOCS_DIR_PHPUML
	fi
	mkdir $DOCS_DIR_PHPUML
fi

if [ "$USE_PHPDOC" == "1" ]; then
	if [[ -d "$DOCS_DIR_PHPDOC" ]]; then
		rm -rf $DOCS_DIR_PHPDOC
	fi
	mkdir $DOCS_DIR_PHPDOC
fi

#Execute applications
echo "Generating docs..."
$PATH_PHPUML -f htmlnew -o $DOCS_DIR_PHPUML $DATA_DIR
$PATH_PHPDOC run -d $DATA_DIR -t $DOCS_DIR_PHPDOC --template clean --quiet --log $LOG_PHPDOC