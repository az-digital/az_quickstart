#!/bin/bash
# Script to quickly create sub-theme.

echo '
+------------------------------------------------------------------------+
| With this script you could quickly create barrio sub-theme             |
| In order to use this:                                                  |
| - bootstrap_barrio theme (this folder) should be in the contrib folder |
+------------------------------------------------------------------------+
'
echo 'The machine name of your custom theme? [e.g. mycustom_barrio]'
read CUSTOM_BARRIO

echo 'Your theme name ? [e.g. My custom barrio]'
read CUSTOM_BARRIO_NAME

if [[ ! -e ../../custom ]]; then
    mkdir ../../custom
fi
cp -r subtheme ../../custom/$CUSTOM_BARRIO
cd ../../custom/$CUSTOM_BARRIO
for file in *bootstrap_barrio_subtheme.*; do mv $file ${file//bootstrap_barrio_subtheme/$CUSTOM_BARRIO}; done
for file in config/*/*bootstrap_barrio_subtheme.*; do mv $file ${file//bootstrap_barrio_subtheme/$CUSTOM_BARRIO}; done
mv {_,}$CUSTOM_BARRIO.theme
grep -Rl bootstrap_barrio_subtheme .|xargs sed -i '' -e "s/bootstrap_barrio_subtheme/$CUSTOM_BARRIO/"
sed -i -e "s/Bootstrap Barrio Subtheme/$CUSTOM_BARRIO_NAME/" $CUSTOM_BARRIO.info.yml
echo "# Check the themes/custom folder for your new sub-theme."
