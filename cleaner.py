import struct
import json

# Function to serialize a Python dictionary to a PHP-style serialized string
def php_serialize(data):
    if isinstance(data, dict):
        serialized = "a:{0}:{{".format(len(data))  # 'a' stands for array
        for key, value in data.items():
            serialized += serialize_php_value(key)
            serialized += serialize_php_value(value)
        serialized += "}"
        return serialized
    elif isinstance(data, list):
        serialized = "a:{0}:{{".format(len(data))  # 'a' stands for array
        for value in data:
            serialized += serialize_php_value(value)
        serialized += "}"
        return serialized
    else:
        return serialize_php_value(data)

def serialize_php_value(value):
    if isinstance(value, str):
        # For strings, we store the length followed by the string
        return 's:{0}:"{1}";'.format(len(value), value)
    elif isinstance(value, int):
        # For integers, we store them as 'i:<value>'
        return 'i:{0};'.format(value)
    elif isinstance(value, float):
        # For floats, we store them as 'd:<value>'
        return 'd:{0};'.format(value)
    elif value is None:
        # For None, we store them as 'N;'
        return 'N;'
    else:
        raise ValueError("Unsupported data type")

# Example Python data

with open('tlds.json') as f:
    data = json.load(f)

raw_dict = {}

for key, value in data.items():

    if (value.get('host') == None) or ("." in key):
        continue
    if value.get("host").startswith("http"):
        continue
    raw_dict[key] = value['host']


with open('clean_tlds.json', 'w') as f:
    json.dump(raw_dict, f)
