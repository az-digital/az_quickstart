###
This file contains tasks only necessary for packaging and publishing Chosen
###
module.exports = (grunt) ->

  grunt.config 'dom_munger',
    latest_version:
      src: ['docs/index.html', 'docs/index.proto.html', 'docs/options.html']
      options:
        callback: ($) ->
          $('#latest-version').text(grunt.config.get('version_tag'))

  grunt.config 'zip',
    chosen:
      cwd: 'docs/'
      src: ['docs/**/*']
      dest: 'chosen_<%= version_tag %>.zip'

  grunt.config 'gh-pages',
    options:
      base: 'public',
      message: 'Updated to new Chosen version <%= pkg.version %>'
    src: ['**']

  grunt.registerTask 'package-npm', 'Generate npm manifest', () ->
    pkg = grunt.config.get('pkg')
    extra = pkg._extra

    json =
      name: "#{pkg.name}-js"
      version: pkg.version
      description: pkg.description
      keywords: pkg.keywords
      homepage: pkg.homepage
      bugs: pkg.bugs
      license: pkg.license
      contributors: pkg.contributors
      dependencies: pkg.dependencies
      files: extra.files
      main: extra.main[0]
      repository: pkg.repository

    grunt.file.write('docs/package.json', JSON.stringify(json, null, 2) + "\n")

  grunt.registerTask 'prep-release', ['build', 'dom_munger:latest_version', 'zip:chosen', 'package-npm']
  grunt.registerTask 'publish-release', ['gh-pages']
