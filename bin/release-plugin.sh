#!/bin/bash

# First some ArtWork, code is poetry
echo '                                                                           ';
echo '       ___       _________     ______________              _____           ';
echo '       __ |     / /__  __ \    ___  __ \__  /___  ________ ___(_)______    ';
echo '       __ | /| / /__  /_/ /    __  /_/ /_  /_  / / /_  __ `/_  /__  __ \   ';
echo '       __ |/ |/ / _  ____/     _  ____/_  / / /_/ /_  /_/ /_  / _  / / /   ';
echo '       ____/|__/  /_/          /_/     /_/  \__,_/ _\__, / /_/  /_/ /_/    ';
echo '                                                   /____/                  ';
echo '_______       _____            ________     ______                         ';
echo '___    |___  ___  /______      ___  __ \_______  /__________ ____________  ';
echo '__  /| |  / / /  __/  __ \     __  /_/ /  _ \_  /_  _ \  __ `/_  ___/  _ \ ';
echo '_  ___ / /_/ // /_ / /_/ /     _  _, _//  __/  / /  __/ /_/ /_(__  )/  __/ ';
echo '/_/  |_\__,_/ \__/ \____/      /_/ |_| \___//_/  \___/\__,_/ /____/ \___/  ';
echo '                                                                           ';

# Check if global parameters are sent correctly
if [ ! -n "$SVN_USERNAME" ]; then
    echo "Environment Variable SVN_USERNAME not defined...";
    export EXITSTATUS=1;
fi
if [ ! -n "$SVN_PASSWORD" ]; then
    echo "Environment Variable SVN_PASSWORD not defined...";
    export EXITSTATUS=1;
fi
if [ ! -n "$SVN_REPO_URL" ]; then
    echo "Environment Variable SVN_REPO_URL not defined...";
    export EXITSTATUS=1;
fi
if [ ! -n "$MAINFILE" ]; then
    echo "Environment Variable MAINFILE (The main file of plugin) not defined...";
    export EXITSTATUS=1;
fi
if [ -n "$EXITSTATUS" ]; then
    echo "Please define the above mentioned environment variables and try again...";
    exit 1;
fi

# Defining all custom parameters
export DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )"/.. && pwd )";
export PLUGINSLUG="$(basename $DIR)";
export GITPATH="$DIR";
export SVNPATH="/tmp/$PLUGINSLUG";
export SVNTRUNK="$SVNPATH/trunk";
export SVNTAGS="$SVNPATH/tags";
export SVNASSETS="$SVNPATH/assets";

# Let's begin...
echo "Preparing to deploy wordpress plugin...";

# Check version in readme.txt is the same as plugin file
export NEWVERSION1=`grep "^Stable tag" $GITPATH/readme.txt | awk -F' ' '{print $3}'`;
echo "readme.txt version: $NEWVERSION1";
export NEWVERSION2=`grep -i "Version" $GITPATH/$MAINFILE | head -n1 | awk -F':' '{print $2}' | awk -F' ' '{print $1}'`;
echo "$MAINFILE version: $NEWVERSION2";

# Exit if versions don't match
if [ "$NEWVERSION1" != "$NEWVERSION2" ]; then
    echo "Versions don't match. Exiting...";
    exit 1;
fi

echo "Versions match in readme.txt and $MAINFILE file. Let's proceed...";

echo "Creating local copy of SVN repo...";
yes yes | svn co $SVN_REPO_URL $SVNPATH --quiet --username=$SVN_USERNAME --password=$SVN_PASSWORD;

# Exit if svn checkout failed
if [ ! -d "$SVNPATH" ]; then
    echo "Could not checkout from SVN. Please check above errors for help. Exiting...";
    exit 1;
fi

# Now check if required folders (trunk, tags and assets) are there in SVN repo
cd "$SVNPATH"
if [ ! -d "$SVNTRUNK" ]; then 
    echo "Creating and committing trunk directory...";
    mkdir "$SVNTRUNK";
    svn add "$SVNTRUNK";
    yes yes | svn commit -m "Trunk directory added" --username=$SVN_USERNAME --password=$SVN_PASSWORD;
    echo "done";
fi

if [ ! -d "$SVNTAGS" ]; then
    echo "Creating and committing tags directory...";
    mkdir "$SVNTAGS";
    svn add "$SVNTAGS";
    yes yes | svn commit -m "Tags directory added" --username=$SVN_USERNAME --password=$SVN_PASSWORD;
    echo "done";
fi

if [ ! -d "$SVNASSETS" ]; then
    echo "Creating and committing assets directory...";
    mkdir "$SVNASSETS";
    svn add "$SVNASSETS";
    yes yes | svn commit -m "Adding assets directory" --username=$SVN_USERNAME --password=$SVN_PASSWORD;
    echo "done";
fi

# Change directory to git repo
cd "$GITPATH";


# If assets directory is there in git repo, try to create assets in SVN
if [ -d "$GITPATH/assets" ]; then
    echo "Assets directory found, syncing assets locally...";
    # Sync assets from git repo to svn repo
    rsync -av --delete "$GITPATH/assets/" "$SVNASSETS/";
    cd $SVNASSETS
    # Check if there are any files to commit before running svn add
    if [[ $(svn status) ]]; then
        echo "Changes in assets detected, updating assets on SVN...";
        # Add only new files to svn if there are any
        if [[ $(svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}') ]]; then
            svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add;
        fi
        # Delete deleted files from svn also
        if [[ $(svn status | grep -v "^.[ \t]*\..*" | grep "^!" | awk '{print $2}') ]]; then
            svn status | grep -v "^.[ \t]*\..*" | grep "^!" | awk '{print $2}' | xargs svn delete;
        fi
        yes yes | svn commit -m "Assets updated $NEWVERSION1" --username=$SVN_USERNAME --password=$SVN_PASSWORD;
        echo "done";
    else
        echo "No changes detected in assets...";
    fi
fi

cd "$GITPATH";
echo "Syncing local svn trunk with git repo...";
rsync -av --delete --exclude-from "$GITPATH/bin/rsync-excludes.txt" "$GITPATH/" "$SVNTRUNK/";

cd $SVNTRUNK

# Check if there are any files to commit before running svn add
if [[ $(svn status) ]]; then
    echo "Changes in trunk detected, updating trunk...";
    # Add only new files to svn if there are any
    if [[ $(svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}') ]]; then
        svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add;
    fi
    # Delete deleted files from svn also
    if [[ $(svn status | grep -v "^.[ \t]*\..*" | grep "^!" | awk '{print $2}') ]]; then
        svn status | grep -v "^.[ \t]*\..*" | grep "^!" | awk '{print $2}' | xargs svn delete;
    fi
    yes yes | svn commit -m "Trunk updated $NEWVERSION1" --username=$SVN_USERNAME --password=$SVN_PASSWORD;
    echo "done";
else
    echo "No changes in trunk, continuing...";
fi

cd "$SVNPATH";
# Check if tag already exists in SVN, if not, then create new
if [ ! -d "$SVNTAGS/$NEWVERSION1" ]; then
    echo "Creating new SVN tag...";
    svn copy "trunk/" "tags/$NEWVERSION1/";
    cd "$SVNPATH/tags/$NEWVERSION1";
    yes yes | svn commit -m "Tagging version $NEWVERSION1" --username=$SVN_USERNAME --password=$SVN_PASSWORD;
else
    echo "Version $version tag already exists, skipping tag creation...";
fi

echo "Deployment Complete :) ";
