import React, { useEffect, useState } from "react";
import ChatWindow from "@/components/ChatWindow.jsx";
import { FaPlus } from "react-icons/fa";
import axios from "axios";

const ChatLayout = ({ rootUrl }) => {
    const userData = document.getElementById("main").getAttribute("data-user");
    const user = JSON.parse(userData);

    const [chats, setChats] = useState([]);     // Recent chats
    const [activeChat, setActiveChat] = useState(null); // Currently open chat

    const getChats = async () => {
        try {
            const res = await axios.get(`${rootUrl}/chats`);
            setChats(res.data);
        } catch (err) {
            console.error(err.message);
        }
    };

    const startNewChat = async () => {
        const name = prompt("Enter username or chat title:");
        if (!name) return;

        try {
            const res = await axios.post(`${rootUrl}/chats`, { name });
            setChats((prev) => [res.data, ...prev]);
            setActiveChat(res.data);
        } catch (err) {
            console.error(err.message);
        }
    };

    useEffect(() => {
        getChats();
    }, []);

    return (
        <div className="chat-layout">
            {/* Sidebar */}
            <div className="chat-sidebar">
                <div className="sidebar-header">
                    <h5>Chats</h5>
                    <button className="new-chat-btn" onClick={startNewChat}>
                        <FaPlus />
                    </button>
                </div>

                <div className="chat-list">
                    {chats.length > 0 ? (
                        chats.map((chat) => (
                            <div
                                key={chat.id}
                                className={`chat-item ${activeChat?.id === chat.id ? "active" : ""}`}
                                onClick={() => setActiveChat(chat)}
                            >
                                <div className="chat-avatar">{chat.name.charAt(0).toUpperCase()}</div>
                                <div className="chat-info">
                                    <h6>{chat.name}</h6>
                                    <small>{chat.last_message || "No messages yet"}</small>
                                </div>
                            </div>
                        ))
                    ) : (
                        <p className="no-chats">No chats yet</p>
                    )}
                </div>
            </div>

            {/* Chat Window */}
            <div className="chat-main">
                {activeChat ? (
                    <ChatWindow rootUrl={rootUrl} chat={activeChat} user={user} />
                ) : (
                    <div className="chat-placeholder">
                        <h4>Select a chat or start a new one</h4>
                    </div>
                )}
            </div>
        </div>
    );
};

export default ChatLayout;
