#!/usr/bin/env python
import sys,imp
try:
    imp.find_module('pychromecast')
    found = True
except ImportError:
    found = False
if found:
    print('ok')
else:
    print('nok')