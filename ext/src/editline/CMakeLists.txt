cmake_minimum_required(VERSION 2.6)
project(php_editline)

include_directories(
  ${simclist_SOURCE_DIR}
  ${bstring_SOURCE_DIR}
  ${common_SOURCE_DIR}
  ${CMAKE_CURRENT_SOURCE_DIR}
  ${CMAKE_CURRENT_BINARY_DIR}
)

add_library(php_editline SHARED editline.c)
target_link_libraries(php_editline /usr/lib64/libedit.so.0)
