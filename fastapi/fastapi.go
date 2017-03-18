package main

import (
	"github.com/dotneet/bpush/fastapi/server"
	"gopkg.in/yaml.v2"
	"io/ioutil"
)

func main() {
	buf, err := ioutil.ReadFile("config.yml")
	if err != nil {
		panic(err)
	}
	config := server.Config{}
	err = yaml.Unmarshal(buf, &config)
	server := server.New(config)
	server.Start()
}
