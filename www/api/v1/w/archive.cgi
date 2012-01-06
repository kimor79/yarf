#!/usr/bin/perl

use strict;
use warnings;

##
## Modules
##

use CGI;
use Config::Simple;
use File::Basename;
use File::Copy::Recursive qw(dircopy);
use File::Path qw(make_path);
use File::Temp qw(tempdir);
use JSON::DWIW;
use Nodegroups::Client;

##
## Global Variables
##

my %ARCHIVE_OPTS;
my @ARCHIVED;
my $CGI;
my $CONFIG_FILE = '/usr/local/etc/yarf/config.ini';
my $DIRECTORY;
my @FAILED;
my $JSON;
my $NGCLIENT;
my %NGCLIENT_OPTS = (
	'user_agent' => 'yarf_archive/0.1',
);
my $NODES;
my %RRD_OPTS;
my $TIME = time();

##
## Subroutines
##

sub _exit {
# Purpose: format output and exit
# Inputs: status, message, details

	my ($status, $message, $details) = @_;

	my $output = $JSON->to_json({
		'details' => $details,
		'message' => $message,
		'status' => $status,
	});

	print $output;
	exit(0);
}

sub add_timestamp {
# Purpose: Add timestamp so rrd_graph has an accurate end time

	my $fh;
	open($fh, '>', $DIRECTORY . '/timestamp') && do {
		print $fh $TIME . "\n";
		close($fh);

		return 1;
	};

	return 0;
}

sub archive {
# Purpose: copy files for a given node
# Inputs: node
# Returns: 1/0

	my $node = shift;

	my @globs = glob($RRD_OPTS{'paths'} . '/' . $node);
	my $count = scalar(@globs);

	if($count < 1) {
		return 0;
	}

	my $success = 0;
	foreach my $path (@globs) {
		my $t_path = dirname($path);
		$t_path =~ s|/|_|g;

		my $dir = $DIRECTORY . '/' . $t_path . '/' . $node;

		eval {
			make_path($dir);
		};

		if($@) {
			return 0;
		}

		if(dircopy($path, $dir)) {
			$success++;
		}
	}

	if($success == $count) {
		return 1;
	}

	return 0;
}

sub parse_config {
# Purpose: Parse a config file
# Inputs: filename
# Returns: true/false

	my $file = shift;

	my $cfg = Config::Simple->new($file);

	if(!defined($cfg)) {
		_exit(500, Config::Simple->error(), {});
	}

	my $config = $cfg->vars();

	while(my ($fkey, $value) = each(%{$config})) {
		my ($ns, $key) = split(/\./, $fkey, 2);

		if($ns eq 'archive') {
			$ARCHIVE_OPTS{$key} = $value;
		} elsif($ns eq 'nodegroups_client') {
			if(index($value, '.')) {
				my ($skey, $svalue) = split(/\./, $key, 2);
				$NGCLIENT_OPTS{$skey}{$svalue} = $value;
			} else {
				$NGCLIENT_OPTS{$key} = $value;
			}
		} elsif($ns eq 'rrd') {
			$RRD_OPTS{$key} = $value;
		}
	}

	return 1;
}

sub setup_directory {
# Purpose: create archive directory

	if(!$ARCHIVE_OPTS{'current'}) {
		_exit(500, 'No archive directory configured', {});
	}

	eval {
		make_path($ARCHIVE_OPTS{'current'});

		$DIRECTORY = tempdir('XXXXXX',
			CLEANUP => 0, DIR => $ARCHIVE_OPTS{'current'});

		chmod(0755, $DIRECTORY);
	};

	if($@) {
		_exit(500, $@, {});
	}

	return 1;
}

##
## Main
##

$CGI = CGI->new();
print $CGI->header();

parse_config($CONFIG_FILE);

$JSON = JSON::DWIW->new();
$NGCLIENT = Nodegroups::Client->new(%NGCLIENT_OPTS);

if($CGI->param('expression')) {
	$NODES =
		$NGCLIENT->get_nodes_from_expression($CGI->param('expression'));
	if(!defined($NODES)) {
		_exit(400, 'Nodegroups: ' . $NGCLIENT->errstr(), {});
	}
} elsif($CGI->param('node')) {
	$NODES = ($CGI->param('node'));
} else {
	_exit(400, 'Missing expression/node', {});
}

if(scalar(@{$NODES}) < 1) {
	_exit(400, 'No nodes given', {});
}

setup_directory();

foreach my $node (@{$NODES}) {
	if(archive($node)) {
		push(@ARCHIVED, $node);
	} else {
		push(@FAILED, $node);
	}
}

my $success = scalar(@ARCHIVED);
if($success < 1) {
	rmdir($DIRECTORY);

	_exit(400, 'Unable to archive', { 'excluded' => \@FAILED });
} else {
	add_timestamp();

	my $message = sprintf("%s archived, %s skipped",
		$success, scalar(@FAILED));
	_exit(200, $message, {
		'archive' => substr($DIRECTORY, -6),
		'excluded' => \@FAILED,
		'included' => \@ARCHIVED,
	});
}
