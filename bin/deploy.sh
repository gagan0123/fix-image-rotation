#! /bin/bash
# A modification of Dean Clatworthy's deploy script as found here: https://github.com/deanc/wordpress-plugin-git-svn
# The difference is that this script lives in the plugin's git repo & doesn't require an existing SVN repo.

# main config
export DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )"/.. && pwd )"
export PLUGINSLUG="$(basename $DIR)"  #must match with wordpress.org plugin slug
export MAINFILE="$PLUGINSLUG.php" # this should be the name of your main php file in the wordpress plugin

##### YOU CAN STOP EDITING HERE #####

# git config
GITPATH="$DIR/" # this file should be in the base of your git repository

# svn config
SVNPATH="/tmp/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="https://plugins.svn.wordpress.org/$PLUGINSLUG/" # Remote SVN repo on wordpress.org, with no trailing slash

# Detect svn username based on url
SVNUSER=$(cat ~/.subversion/auth/svn.simple/* | grep -A4 $(echo $SVNURL | awk -F// '{print $2}' | cut     -d'/' -f1) | tail -n1)
if [ -z "$SVNUSER" ]
then
	SVNUSER="gagan0123"
fi


# Let's begin...
echo ".........................................."
echo
echo "Preparing to deploy wordpress plugin"
echo
echo ".........................................."
echo

# Check version in readme.txt is the same as plugin file
NEWVERSION1=`grep "^Stable tag" $GITPATH/readme.txt | awk -F' ' '{print $3}'`
echo "readme version: $NEWVERSION1"
#NEWVERSION2=`grep "^Version" $GITPATH/$MAINFILE | awk -F' ' '{print $2}'`
NEWVERSION2=`grep -i "Version" $GITPATH/$MAINFILE | head -n1 | awk -F':' '{print $2}' | awk -F' ' '{print $1}'`
echo "$MAINFILE version: $NEWVERSION2"

if [ "$NEWVERSION1" != "$NEWVERSION2" ]; then echo "Versions don't match. Exiting...."; exit 1; fi

echo "Versions match in readme.txt and PHP file. Let's proceed..."

cd $GITPATH

# Variables List
TITLE=$(head -n1 readme.txt)
LICENSE=$(cat readme.txt | grep "License URI:" | awk -F// '{ print $2 }' |  cat readme.txt | grep "License URI:" | cut -d: -f2,3)

# Remove Previous Files
if [ -e /tmp/file ] || [ -e /tmp/file1 ] || [ -e /tmp/file2 ]
then
        rm /tmp/file* &> /dev/null
fi

# Add Images
curl -I $1/assets/banner-772x250.png 2> /dev/null | grep '200 OK' &> /dev/null
if [ $? -eq 0 ]
then
	echo "![alt text]($1/assets/banner-772x250.png)" &> /tmp/file
	echo >> /tmp/file
fi
curl -I $1/assets/banner-772x250.jpg 2> /dev/null | grep '200 OK' &> /dev/null
if [ $? -eq 0 ]
then
	echo "![alt text]($1/assets/banner-772x250.jpg)" &> /tmp/file
	echo >> /tmp/file
fi
curl -I $1/assets/banner-772x250.jpeg 2> /dev/null | grep '200 OK' &> /dev/null
if [ $? -eq 0 ]
then
	echo "![alt text]($1/assets/banner-772x250.jpeg)" &> /tmp/file
	echo >> /tmp/file
fi

# Add Title & Contribute To Temp File
head -n1 readme.txt >> /tmp/file
echo -n Contributors: >> /tmp/file

# Find No Of Contributors & Send Them To Temp File
for i in $(cat readme.txt | grep ^Contributor | cut -d: -f2 | tr ',' ' ')
do
        echo -n " [$i] (http://profiles.wordpress.org/$i)," | tr '\n' ' ' 
done >> /tmp/file
echo >> /tmp/file

# Find License Details
echo $LICENSE | grep 3.0 
if [ $? -eq 0 ] 
then
        LICENSE="[GPL v3 or later] (http://www.gnu.org/licenses/gpl-3.0.html)"
else
        LICENSE="[GPL v2 or later] ($LICENSE)"
fi

# Send License Details To Temp File
echo "License: $LICENSE" >> /tmp/file


# Send All The Line Except The Lines All Ready Present in Temp File
cat readme.txt | grep -v "$TITLE" | grep -v Contributors | grep -v License >> /tmp/file


# Delete Unwanted Stuff
sed '/^Tags:/,/^Stable tag:/d' /tmp/file &>/tmp/file1
sed '/^== Upgrade/,/$/d' /tmp/file1 &> /tmp/file2

# Add New Line (Needed To Proper Solutions)
#sed -i '/Donate/ i\License: [GPLv2 or later] (http://www.gnu.org/licenses/gpl-2.0.html)' /tmp/file2

# Add New Lines For Line Breaks In Github
sed 's/Contributors/\'$'\n&/g' /tmp/file2 &> /tmp/file1
sed 's/License/\'$'\n&/g' /tmp/file1 &> /tmp/file2
sed 's/Donate/\'$'\n&/g' /tmp/file2 &> /tmp/file1



# Replace === to #
sed 's/===/#/g' /tmp/file1 &> /tmp/file2

# REplace == to ##
sed 's/==/##/g' /tmp/file2 &> /tmp/file1

# Replave = to #### From Description To The End Of File
sed '/Description/,$s/=/####/g' /tmp/file1 &> /tmp/file2

# Make Text Bold
sed 's/[Cc]ontributors:/* **Contributors:**/' /tmp/file2 &> /tmp/file1
sed 's/[Dd]onate [Ll]ink:/* **Donate Link:**/' /tmp/file1 &> /tmp/file2
sed 's/[Ll]icense:/* **License:**/' /tmp/file2 &> README.md

echo -e "Enter a commit message for this new version: \c"
read COMMITMSG
git commit -am "$COMMITMSG"

echo "Tagging new version in git"
git tag -a "$NEWVERSION1" -m "Tagging version $NEWVERSION1"

echo "Pushing latest commit to origin, with tags"
git push origin master
git push origin master --tags

echo
echo "Creating local copy of SVN repo ..."
svn co $SVNURL $SVNPATH

echo "Exporting the HEAD of master from git to the trunk of SVN"
git checkout-index -a -f --prefix=$SVNPATH/trunk/

echo "Ignoring github specific files and deployment script"
svn propset svn:ignore "deploy.sh
deploy-common.sh
readme.sh
README.md
bin
.git
.gitattributes
.gitignore
map.conf
nginx.log
tests
Gruntfile.js
package.json
phpunit.xml
phpunit.xml.dist
package-lock.json
node_modules
.sass-cache
.gitlab-ci.yml
.travis.yml" "$SVNPATH/trunk/"

echo "Changing directory to SVN and committing to trunk"
cd $SVNPATH/trunk/
# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add
svn commit --username=$SVNUSER -m "$COMMITMSG"

echo "Creating new SVN tag & committing it"
cd $SVNPATH
svn copy trunk/ tags/$NEWVERSION1/
cd $SVNPATH/tags/$NEWVERSION1
svn commit --username=$SVNUSER -m "Tagging version $NEWVERSION1"

echo "Removing temporary directory $SVNPATH"
rm -fr $SVNPATH/

echo "*** FIN ***"
