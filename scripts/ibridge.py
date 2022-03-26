#!/usr/local/bin/python3

"""
iBridge for munkireport.
Will return all details about iBridge in the machine
"""

import subprocess
import os
import plistlib
import sys
import platform
import re
import string

def getOsVersion():
    """Returns the minor OS version."""
    os_version_tuple = platform.mac_ver()[0].split('.')
    return int(os_version_tuple[1])

def get_ibridge_info():
    '''Uses system profiler to get dev tools for this machine.'''
    cmd = ['/usr/sbin/system_profiler', 'SPiBridgeDataType', '-xml']
    proc = subprocess.Popen(cmd, shell=False, bufsize=-1,
                            stdin=subprocess.PIPE,
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    (output, unused_error) = proc.communicate()
    try:
        try:
            plist = plistlib.readPlistFromString(output)
        except AttributeError as e:
            plist = plistlib.loads(output)
        # system_profiler xml is an array
        ibridge_dict = plist[0]
        items = ibridge_dict['_items']
        return items
    except Exception:
        return {}

def get_remotectl_data():

    cmd = ['/usr/libexec/remotectl', 'dumpstate']
    proc = subprocess.Popen(cmd, shell=False, bufsize=-1,
                            stdin=subprocess.PIPE,
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    (output, unused_error) = proc.communicate()

    out = {}

    for item in output.decode().split('\n'):
        if '		AppleInternal => ' in item:
            out['apple_internal'] = to_bool(remove_all('		AppleInternal => ', item).strip())
        elif '		HWModel => ' in item:
            out['hardware_model'] = remove_all('		HWModel => ', item).strip()
        elif '		RegionInfo => ' in item:
            out['region_info'] = remove_all('		RegionInfo => ', item).strip()
#        elif '		BuildVersion => ' in item:
#            out['build'] = remove_all('		BuildVersion => ', item).strip()
        elif '		OSVersion => ' in item:
            out['os_version'] = remove_all('		OSVersion => ', item).strip()
        elif '		BridgeVersion => ' in item:
            out['ibridge_version'] = remove_all('		BridgeVersion => ', item).strip()
        elif '		ProductType => ' in item:
            out['model_identifier'] = remove_all('		ProductType => ', item).strip()
#        elif '		BootSessionUUID => ' in item:
#            out['boot_uuid'] = remove_all('		BootSessionUUID => ', item).strip()
        elif '		BoardId => ' in item:
            out['board_id'] = remove_all('		BoardId => ', item).strip()
#        elif '		DeviceColor => ' in item:
#            out['device_color'] = remove_all('		DeviceColor => ', item).strip().capitalize()
#        elif '		DeviceEnclosureColor => ' in item:
#            out['device_color'] = remove_all('		DeviceEnclosureColor => ', item).strip().capitalize()
        elif '		ModelNumber => ' in item:
            out['model_number'] = remove_all('		ModelNumber => ', item).strip()
    return out

def flatten_ibridge_info(array):
    '''Un-nest dev info, return array with objects with relevant keys'''
    out = []
    for obj in array:
        ibridge = {}
        for item in obj:
            if item == '_items':
                out = out + flatten_ibridge_info(obj['_items'])
            elif item == 'ibridge_boot_uuid':
                ibridge['boot_uuid'] = obj[item]
            elif item == 'ibridge_build':
                ibridge['build'] = obj[item]
#            elif item == 'ibridge_model_identifier':
#                ibridge['model_identifier'] = obj[item]
            elif item == 'ibridge_model_name':
                ibridge['model_name'] = obj[item]
            elif item == 'ibridge_serial_number':
                ibridge['ibridge_serial_number'] = obj[item]
           
        out.append(ibridge)
        
    # Check that we have data to return
    if len(out) > 0:
        return out[0]
    else:
        return {}
    
def to_bool(s):
    if s == True or "true" in s or "enabled" in s:
        return 1
    else:
        return 0

def remove_all(substr, str):
    return str.replace(substr, "")

def merge_two_dicts(x, y):
    z = x.copy()
    z.update(y)
    return z


def main():
    """Main"""
    # Check OS version and skip if too old       
    if getOsVersion() < 12:
        print('Skipping iBridge check, OS does not support iBridge')
        exit(0)

    # Get results
    result = dict()
    info = get_ibridge_info()
    result = merge_two_dicts(flatten_ibridge_info(info),get_remotectl_data())

    # Write ibridge results to cache
    cachedir = '%s/cache' % os.path.dirname(os.path.realpath(__file__))
    output_plist = os.path.join(cachedir, 'ibridge.plist')
    try:
        plistlib.writePlist(result, output_plist)
    except:
        with open(output_plist, 'wb') as fp:
            plistlib.dump(result, fp, fmt=plistlib.FMT_XML)

if __name__ == "__main__":
    main()
