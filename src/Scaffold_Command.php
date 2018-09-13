<?php

use EE\Process;
use EE\Utils;

class Scaffold_Command extends EE_Command {

	/**
	 * Generate a README.md for your command.
	 *
	 * Creates a README.md with Using, Installing, and Contributing instructions
	 * based on the composer.json file for your EE package. Run this command
	 * at the beginning of your project, and then every time your usage docs
	 * change.
	 *
	 * These command-specific docs are generated based composer.json -> 'extra'
	 * -> 'commands'. For instance, this package's composer.json includes:
	 *
	 * ```
	 * {
	 *   "name": "easyengine/scaffold-command",
	 *    // [...]
	 *    "extra": {
	 *        "commands": [
	 *            "scaffold package-readme"
	 *        ]
	 *    }
	 * }
	 * ```
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : Directory path to an existing package to generate a readme for.
	 *
	 * [--force]
	 * : Overwrite the readme if it already exists.
	 *
	 * @subcommand package-readme
	 */
	public function package_readme( $args, $assoc_args ) {

		list( $package_dir ) = $args;

		self::check_if_valid_package_dir( $package_dir );

		$composer_obj = json_decode( file_get_contents( $package_dir . '/composer.json' ), true );
		if ( ! $composer_obj ) {
			EE::error( 'Invalid composer.json in package directory.' );
		}

		$force = Utils\get_flag_value( $assoc_args, 'force' );

		$package_root = dirname( dirname( __FILE__ ) );
		$template_path = $package_root . '/templates/';

		$bits = explode( '/', $composer_obj['name'] );
		$readme_args = array(
			'package_name'        => $composer_obj['name'],
			'package_short_name'  => $bits[1],
			'package_name_border' => str_pad( '', strlen( $composer_obj['name'] ), '=' ),
			'package_description' => isset( $composer_obj['description'] ) ? $composer_obj['description'] : '',
			'shields'             => '',
			'has_commands'        => false,
			'ee_update_to_instructions' => 'the latest stable release with `wp cli update`',
			'show_powered_by'     => isset( $composer_obj['extra']['readme']['show_powered_by'] ) ? (bool) $composer_obj['extra']['readme']['show_powered_by'] : true,
		);

		if ( isset( $composer_obj['extra']['readme']['shields'] ) ) {
			$readme_args['shields'] = implode( ' ', $composer_obj['extra']['readme']['shields'] );
		} else {
			$shields = array();
			if ( file_exists( $package_dir . '/.travis.yml' ) ) {
				$shields[] = "[![Build Status](https://travis-ci.org/{$readme_args['package_name']}.svg?branch=master)](https://travis-ci.org/{$readme_args['package_name']})";
			}
			if ( file_exists( $package_dir . '/circle.yml' ) ) {
				$shields[] = "[![CircleCI](https://circleci.com/gh/{$readme_args['package_name']}/tree/master.svg?style=svg)](https://circleci.com/gh/{$readme_args['package_name']}/tree/master)";
			}

			if ( count( $shields ) ) {
				$readme_args['shields'] = implode( ' ', $shields );
			}
		}

		if ( ! empty( $composer_obj['extra']['commands'] ) ) {
			$readme_args['commands'] = array();
			$cmd_dump = EE::runcommand( 'site cmd-dump', array( 'launch' => false, 'return' => true, 'parse' => 'json' ) );
			foreach( $composer_obj['extra']['commands'] as $command ) {
				$bits = explode( ' ', $command );
				$parent_command = $cmd_dump;
				do {
					$cmd_bit = array_shift( $bits );
					$found = false;
					foreach( $parent_command['subcommands'] as $subcommand ) {
						if ( $subcommand['name'] === $cmd_bit ) {
							$parent_command = $subcommand;
							$found = true;
							break;
						}
					}
					if ( ! $found ) {
						$parent_command = false;
					}
				} while( $parent_command && $bits );

				if ( empty( $parent_command ) ) {
					EE::error( 'Missing one or more commands defined in composer.json -> extra -> commands.' );
				}

				$longdesc = preg_replace( '/## GLOBAL PARAMETERS(.+)/s', '', $parent_command['longdesc'] );
				$longdesc = preg_replace( '/##\s(.+)/', '**$1**', $longdesc );

				// definition lists
				$longdesc = preg_replace_callback( '/([^\n]+)\n: (.+?)(\n\n|$)/s', array( __CLASS__, 'rewrap_param_desc' ), $longdesc );

				$readme_args['commands'][] = array(
					'name' => "ee {$command}",
					'shortdesc' => $parent_command['description'],
					'synopsis' => "ee {$command}" . ( empty( $parent_command['subcommands'] ) ? " {$parent_command['synopsis']}" : "" ),
					'longdesc' => $longdesc,
				);
			}
			$readme_args['has_commands'] = true;
			$readme_args['has_multiple_commands'] = count( $readme_args['commands'] ) > 1 ? true : false;
		}

		if ( isset( $composer_obj['extra']['readme']['sections'] ) ) {
			$readme_section_headings = $composer_obj['extra']['readme']['sections'];
		} else {
			$readme_section_headings = array(
				'Using',
				'Contributing',
				'Support',
			);
		}

		$readme_sections = array();
		foreach( $readme_section_headings as $section_heading ) {
			$key = strtolower( preg_replace( '#[^\da-z-_]#i', '', $section_heading ) );
			$readme_sections[ $key ] = array(
				'heading'      => $section_heading,
			);
		}
		$bundled = ! empty( $composer_obj['extra']['bundled'] );
		foreach( array( 'using', 'contributing', 'support' ) as $key ) {
			if ( isset( $readme_sections[ $key ] ) ) {
				$file = dirname( dirname( __FILE__ ) ) . '/templates/readme-' . $key . '.mustache';
				if ( $bundled
					&& file_exists( dirname( dirname( __FILE__ ) ) . '/templates/readme-' . $key . '-bundled.mustache' ) ) {
					$file = dirname( dirname( __FILE__ ) ) . '/templates/readme-' . $key . '-bundled.mustache';
				}
				$readme_sections[ $key ]['body'] = $file;
			}
		}

		$readme_sections['package_description'] = array(
			'body' => isset( $composer_obj['description'] ) ? $composer_obj['description'] : '',
		);

		$readme_args['quick_links'] = '';
		foreach( $readme_sections as $key => $section ) {
			if ( ! empty( $section['heading'] ) ) {
				$readme_args['quick_links'] .= '[' . $section['heading'] . '](#' . $key . ') | ';
			}
		}
		if ( ! empty( $readme_args['quick_links'] ) ) {
			$readme_args['quick_links'] = 'Quick links: ' . rtrim( $readme_args['quick_links'], '| ' );
		}

		$readme_args['sections'] = array();
		$ext_regex = '#\.(md|mustache)$#i';
		foreach( $readme_sections as $section => $section_args ) {
			$value = array();
			foreach( array( 'pre', 'body', 'post' ) as $k ) {
				$v = '';
				if ( isset( $composer_obj['extra']['readme'][ $section ][ $k ] ) ) {
					$v = $composer_obj['extra']['readme'][ $section][ $k ];
					if ( false !== stripos( $v, '://' ) ) {
						$response = Utils\http_request( 'GET', $v );
						$v = $response->body;
					} else if ( preg_match( $ext_regex, $v ) ) {
						$v = $package_dir . '/' . $v;
					}
				} else if ( isset( $section_args[ $k ] ) ) {
					$v = $section_args[ $k ];
				}
				if ( $v ) {
					if ( preg_match( $ext_regex, $v ) ) {
						$v = Utils\mustache_render( $v, $readme_args );
					}
					$value[] = trim( $v );
				}
			}
			$value = trim( implode( PHP_EOL . PHP_EOL, $value ) );
			if ( 'package_description' === $section ) {
				$readme_args['package_description'] = $value;
			} else {
				$readme_args['sections'][] = array(
					'heading'      => $section_args['heading'],
					'body'         => $value,
				);
			}
		}

		$files_written = $this->create_files( array(
			"{$package_dir}/README.md" => Utils\mustache_render( "{$template_path}/readme.mustache", $readme_args ),
		), $force );

		if ( empty( $files_written ) ) {
			EE::log( 'Package readme generation skipped.' );
		} else {
			EE::success( 'Created package readme.' );
		}
	}

	private static function rewrap_param_desc( $matches ) {
		$param = $matches[1];
		$desc = self::indent( "\t\t", $matches[2] );
		return "\t$param\n$desc\n\n";
	}

	private static function indent( $whitespace, $text ) {
		$lines = explode( "\n", $text );
		foreach ( $lines as &$line ) {
			$line = $whitespace . $line;
		}
		return implode( $lines, "\n" );
	}

	private function prompt_if_files_will_be_overwritten( $filename, $force ) {
		$should_write_file = true;
		if ( ! file_exists( $filename ) ) {
			return true;
		}

		EE::warning( 'File already exists' );
		EE::log( $filename );
		if ( ! $force ) {
			do {
				$answer = \cli\prompt(
					'Skip this file, or replace it with scaffolding?',
					$default = false,
					$marker = '[s/r]: '
				);
			} while ( ! in_array( $answer, array( 's', 'r' ) ) );
			$should_write_file = 'r' === $answer;
		}

		$outcome = $should_write_file ? 'Replacing' : 'Skipping';
		EE::log( $outcome . PHP_EOL );

		return $should_write_file;
	}

	private function create_files( $files_and_contents, $force ) {
		$wrote_files = array();

		foreach ( $files_and_contents as $filename => $contents ) {
			$should_write_file = $this->prompt_if_files_will_be_overwritten( $filename, $force );
			if ( ! $should_write_file ) {
				continue;
			}

			if ( ! is_dir( dirname( $filename ) ) ) {
				Process::create( Utils\esc_cmd( 'mkdir -p %s', dirname( $filename ) ) )->run();
			}

			if ( ! file_put_contents( $filename, $contents ) ) {
				EE::error( "Error creating file: $filename" );
			} elseif ( $should_write_file ) {
				$wrote_files[] = $filename;
			}
		}
		return $wrote_files;
	}

	private static function check_if_valid_package_dir( $package_dir ) {
		if ( ! is_dir( $package_dir ) ) {
			EE::error( 'Directory does not exist.' );
		}

		if ( ! file_exists( $package_dir . '/composer.json' ) ) {
			EE::error( 'Invalid package directory. composer.json file must be present.' );
		}
	}

}
