#!/usr/local/munkireport/munkireport-python3

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
    """Returns the Darwin version."""
    # Catalina -> 10.15.7 -> 19.6.0 -> 19
    # os_version_tuple = platform.mac_ver()[0].split('.')
    # return int(os_version_tuple[1])
    darwin_version_tuple = platform.release().split('.')
    return int(darwin_version_tuple[0]) 

def get_ibridge_info():
    '''Uses system profiler to get iBridge data for this machine.'''
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
    mac_model = ""

    for ibridge in output.decode().split("UUID: "):

        # We need to get the actual T1/T2 chip or the AS CPU itself, not the Intel CPU, Studio Display, or connected iOS devices
        if "BoardId => " in ibridge and ("ProductName => macOS" in ibridge or "Product Type: iBridge" in ibridge):

            for item in ibridge.split('\n'):
                # print(item)
                if 'AppleInternal => ' in item:
                    out['apple_internal'] = to_bool(remove_all('AppleInternal => ', item).strip())
                elif 'HWModel => ' in item:
                    out['hardware_model'] = remove_all('HWModel => ', item).strip()
                elif 'RegionInfo => ' in item:
                    out['region_info'] = remove_all('RegionInfo => ', item).strip()
                elif 'OSVersion => ' in item:
                    out['os_version'] = remove_all('OSVersion => ', item).strip()
                elif 'BridgeVersion => ' in item:
                    # Stored elsewhere for T1, see below
                    out['ibridge_version'] = remove_all('BridgeVersion => ', item).strip()
                elif 'Product Type: ' in item:
                    out['model_identifier'] = remove_all('Product Type: ', item).strip()
                elif 'BoardId => ' in item:
                    out['board_id'] = remove_all('BoardId => ', item).strip()
                elif 'ModelNumber => ' in item:
                    out['model_number'] = remove_all('ModelNumber => ', item).strip()
                elif 'DeviceEnclosureColor => ' in item:
                    device_color = remove_all('DeviceEnclosureColor => ', item).strip()

                    '''Uses system profiler to get machine model for this machine.'''
                    cmd = ["/usr/sbin/system_profiler", "SPHardwareDataType", "-xml"]
                    proc = subprocess.Popen(cmd, shell=False, bufsize=-1,
                                            stdin=subprocess.PIPE,
                                            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    (output, unused_error) = proc.communicate()
                    try:
                        try:
                            plist = plistlib.readPlistFromString(output)
                        except AttributeError as e:
                            plist = plistlib.loads(output)
                        mac_model = plist[0]['_items'][0]["machine_name"] # MacBook Pro
                        mac_id = plist[0]['_items'][0]["machine_model"] # MacBookPro16,3
                    except Exception:
                        mac_model = ""
                        mac_id = ""

                    # Override some colors by model
                    if mac_id == "Macmini8,1" or mac_id == "iMacPro1,1" or device_color == "2":
                        out['device_color'] = "Space Gray"
                    elif mac_id == "iMac20,1" or mac_id == "iMac20,2" or mac_model == "Mac mini" or mac_model == "Mac Pro" or mac_model == "Mac Studio" or device_color == "1":
                        out['device_color'] = "Silver"
                    elif device_color == "3" and mac_model == "iMac": # iMac only
                        out['device_color'] = "Yellow"
                    elif device_color == "3"and mac_model == "MacBook Air": # MacBook Air only
                        out['device_color'] = "Gold"
                    elif device_color == "4":
                        out['device_color'] = "Green"
                    elif device_color == "5":
                        out['device_color'] = "Blue"
                    elif device_color == "6":
                        out['device_color'] = "Red"
                    elif device_color == "7" and mac_model == "iMac": # iMac only
                        out['device_color'] = "Purple"
                    elif device_color == "7" and mac_model == "MacBook Air": # MacBook Air only
                        out['device_color'] = "Midnight"
                    elif device_color == "8" and mac_model == "iMac": # iMac only
                        out['device_color'] = "Orange"
                    elif device_color == "8" and mac_model == "MacBook Air": # MacBook Air only
                        out['device_color'] = "Starlight"
                    elif device_color == "9":
                        out['device_color'] = "Space Black"
                    else:
                        out['device_color'] = ""

        elif "ProductType => MacBookPro13,2" in ibridge or "ProductType => MacBookPro13,3" in ibridge or "ProductType => MacBookPro14,2" in ibridge or "ProductType => MacBookPro14,3" in ibridge:
            # T1 Chip has BridgeVersion in the Intel CPU section
            for item in ibridge.split('\n'):
                if 'BridgeVersion => ' in item:
                    out['ibridge_version'] = remove_all('BridgeVersion => ', item).strip()

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
    # Needs at least macOS Sierra (Darwin 16)   
    if getOsVersion() < 16:
        print('Skipping iBridge check, OS does not support iBridge')
        exit(0)

    # Get results
    result = dict()
    info = get_ibridge_info()

    if info:
        # If we have iBridge data
        result = merge_two_dicts(flatten_ibridge_info(info),get_remotectl_data())
    else:
        # Else if no iBridge
        result = {}

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
