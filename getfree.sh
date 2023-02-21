#!/bin/bash
df -k --output="avail" --block-size=1M / | tail -1
