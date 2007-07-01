<?php
# Backward compatibility functions for PHP4
# 
# Copyright (C) 2007  Sylvain Beucler
#
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

#input_is_safe();
#mysql_is_safe();

// Appears in PHP5
function debug_print_backtrace()
{
  //var_dump(debug_backtrace());
  $bt = debug_backtrace();
  array_shift($bt); # remove this very function
  $i = 0;
  foreach($bt as $frame)
    {
      echo "#$i  ";
      echo $frame['function'];
      echo '(';
      echo implode(', ', $frame['args']);
      echo ')';
      echo ' called at [';
      echo $frame['file'];
      echo ':';
      echo $frame['line'];
      echo ']';
      echo '<br />';
      $i++;
    }
}

function memory_get_peak_usage() {
  // Needs PHP5
  return 0;
}
